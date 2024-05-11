<?php
/*
  Descrição do Desafio:
    Você precisa realizar uma migração dos dados fictícios que estão na pasta <dados_sistema_legado> para a base da clínica fictícia MedicalChallenge.
    Para isso, você precisa:
      1. Instalar o MariaDB na sua máquina. Dica: Você pode utilizar Docker para isso;
      2. Restaurar o banco da clínica fictícia Medical Challenge: arquivo <medical_challenge_schema>;
      3. Migrar os dados do sistema legado fictício que estão na pasta <dados_sistema_legado>:
        a) Dica: você pode criar uma função para importar os arquivos do formato CSV para uma tabela em um banco temporário no seu MariaDB.
      4. Gerar um dump dos dados já migrados para o banco da clínica fictícia Medical Challenge.
*/

// Importação de Bibliotecas:
include "./lib.php";

// Conexão com o banco da clínica fictícia:
$connMedical = mysqli_connect("localhost", "root", "root", "MedicalChallenge")
  or die("Não foi possível conectar os servidor MySQL: MedicalChallenge\n");

// Conexão com o banco temporário:
//$connTemp = mysqli_connect("localhost", "root", "root", "0temp")
// or die("Não foi possível conectar os servidor MySQL: 0temp\n");

// Informações de Inicio da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";


//função para gerar o id
function gerarId($conexao, $tabela)
{
  $idInicial = 1;

  $buscarIdSalvo = "SELECT MAX(id) as ultimoID FROM $tabela WHERE id < 10276";
  $resultado = $conexao->query($buscarIdSalvo);

  if ($resultado) {
    $linhasRetornadas = $resultado->fetch_assoc();
    $idInicial = $linhasRetornadas['ultimoID'];
  }

  $proximoId = $idInicial + 1;


  return $proximoId;
}


//limpandos os dados antes da inserção
$limpezaAgendamentos = "DELETE FROM agendamentos WHERE id > 3";
$limpezaPacientes = "DELETE FROM pacientes WHERE id > 1 and id < 10276;";
$limpezaConvenios = "DELETE FROM convenios WHERE id >= 5;";
$limpezaMedicos = "DELETE FROM profissionais WHERE id < 85217;";

$connMedical->query($limpezaAgendamentos);
$connMedical->query($limpezaPacientes);
$connMedical->query($limpezaConvenios);
$connMedical->query($limpezaMedicos);




$h = fopen("/home/gabriel/Downloads/migration-challenge-main/dados_sistema_legado/20210512_pacientes.csv", "r")
  or die("Não foi possivel abrir o arquivo csv de pacientes");

$cabeçalho = true;
$convenioInserido = array();

while (($dados = fgetcsv($h, 1000, ";")) !== false) {

  if ($cabeçalho) {
    $cabeçalho = false;
    continue;
  }

  $codReferencia = $dados[0];
  $nome = $dados[1];
  $nascimento = $dados[2];
  $dataFormatada = DateTime::createFromFormat('d/m/Y', $nascimento)->format('Y-m-d');
  $cpf = $dados[5];
  $rg = $dados[6];
  $sexo = $dados[7];
  $idConvenioLegado = intval($dados[8]);
  $convenio = $dados[9];
  if ($sexo === 'M') {
    $sexo = 'Masculino';
  } else {
    $sexo = 'Feminino';
  }
  //inserindo convenios
  if (!in_array($convenio, $convenioInserido)) {
    //verificando para não inserir convenios iguais
    $idConvenio = gerarId($connMedical, 'convenios');
    $insertConvenio = "INSERT INTO convenios (id,nome, descricao)VALUES ($idConvenio,'$convenio','$convenio')";
    $connMedical->query($insertConvenio) or die("Ocorreu um erro ao inserir o convenio");
    //coloando no array para comparar
    $convenioInserido[] = $convenio;
  }

  //buscando o id salvo dos convenios para inserir nos pacientes
  $selectConvenio = "SELECT id FROM convenios WHERE nome = '$convenio'";
  $resultQuery = $connMedical->query($selectConvenio) or die("Ocorreu um erro ao selecionar o convenio");
  $linhas = $resultQuery->fetch_assoc();
  $idSalvo = $linhas['id'];

  //inserindo os pacientes
  $id = gerarId($connMedical, 'pacientes');
  $insertPaciente = $connMedical->prepare("INSERT INTO pacientes (id,nome, sexo, nascimento, cpf, rg, id_convenio, cod_referencia) 
  VALUES (?,?, ?, ?, ?, ?, ?, ?)");
  $insertPaciente->bind_param("isssssii", $id, $nome, $sexo, $dataFormatada, $cpf, $rg, $idSalvo, $codReferencia);
  $insertPaciente->execute() or die("Ocorreu um erro ao inserir o paciente");
}
fclose($h);

$medicoInserido = array();
$cabeçalho = true;
$i = fopen("/home/gabriel/Downloads/migration-challenge-main/dados_sistema_legado/20210512_agendamentos.csv", "r")
  or die("Não foi possivel abrir o arquivo csv de agendamentos");

while (($dadosAgendamentos = fgetcsv($i, 1000, ";"))) {

  if ($cabeçalho) {
    $cabeçalho = false;
    continue;
  }

  //Inserindo profissionais

  $nomeMedico = $dadosAgendamentos[8];
  if (!in_array($nomeMedico, $medicoInserido)) {
    //verificando para não inserir medicos iguais
    $idMedico = gerarId($connMedical, 'profissionais');
    $insertMedico = $connMedical->prepare("INSERT INTO profissionais (id,nome) VALUES (?,?)");
    $insertMedico->bind_param("is", $idMedico, $nomeMedico);
    $insertMedico->execute() or die("Ocorreu um erro ao inserrir o medico");
    //coloando no array para comparar
    $medicoInserido[] = $nomeMedico;
  }


  //Inserindo Agendamentos

  //buscando id do paciente
  $nomePaciente = $dadosAgendamentos[6];
  $selectPaciente = "SELECT id FROM pacientes WHERE nome = '$nomePaciente';";
  $resultQuery = $connMedical->query($selectPaciente) or die("Ocorreu um erro ao buscar o paciente");
  $linhas = $resultQuery->fetch_assoc();
  $idPacienteSalvo = $linhas['id'];


  //buscamento id do medico
  $selectMedico = "SELECT id FROM profissionais WHERE nome = '$nomeMedico';";
  $resultQuery = $connMedical->query($selectMedico) or die("Ocorreu um erro ao buscar o medico");
  $linhas = $resultQuery->fetch_assoc();
  $idMedico = $linhas['id'];

  //buscando id do convenio
  $nomeConvenio = $dadosAgendamentos[10];
  $selectConvenio = "SELECT id FROM convenios WHERE nome = '$nomeConvenio';";
  $resultQuery = $connMedical->query($selectConvenio) or die("Ocorreu um erro ao buscar o convenio");
  $linhas = $resultQuery->fetch_assoc();
  $idConvenio = $linhas['id'];

  //buscando id do procedimento
  $nomeProcedimento = $dadosAgendamentos[11];
  $selectProcedimento = "SELECT id FROM procedimentos WHERE nome = '$nomeProcedimento'";
  $resultQuery = $connMedical->query($selectProcedimento) or die("Ocorreu um erro ao buscar o procedimento");
  $linhas = $resultQuery->fetch_assoc();
  $idProcedimento = $linhas['id'];

  //formantando o campo date
  $dataInicio = $dadosAgendamentos[2] . ' ' . $dadosAgendamentos[3];
  $dataFim = $dadosAgendamentos[2] . ' ' . $dadosAgendamentos[4];
  $dataFormatadaInicio = DateTime::createFromFormat('d/m/Y H:i:s', $dataInicio)->format('Y-m-d H:i:s');
  $dataFormatadaFim = DateTime::createFromFormat('d/m/Y H:i:s', $dataFim)->format('Y-m-d H:i:s');
  $Observacoes = $dadosAgendamentos[1];


  $id = gerarId($connMedical, 'agendamentos');
  $insertPaciente = $connMedical->prepare("INSERT INTO agendamentos (id, id_paciente, id_profissional, dh_inicio, dh_fim, id_convenio, id_procedimento, observacoes) 
  VALUES (?,?, ?, ?, ?, ?, ?, ?)");
  $insertPaciente->bind_param("iiissiis", $id, $idPacienteSalvo, $idMedico, $dataFormatadaInicio, $dataFormatadaFim, $idConvenio, $idProcedimento, $Observacoes);
  $insertPaciente->execute() or die("Ocorreu um erro ao inserir o agendamento");
}


fclose($i);



// Encerrando as conexões:
$connMedical->close();
//$connTemp->close();

// Informações de Fim da Migração:
echo "Fim da Migração: " . dateNow() . ".\n";
