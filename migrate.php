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
$connMedical = mysqli_connect("localhost", "root", "%Logx3296#", "MedicalChallenge")
  or die("Não foi possível conectar os servidor MySQL: MedicalChallenge\n");

// Conexão com o banco temporário:
//$connTemp = mysqli_connect("localhost", "root", "root", "0temp")
 // or die("Não foi possível conectar os servidor MySQL: 0temp\n");

// Informações de Inicio da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";

//limpandos os dados antes da inserção
$limpezaConvenios = "DELETE FROM convenios WHERE id >= 5;";
$limpezaPacientes = "DELETE FROM pacientes WHERE id > 1 and id < 10272;";
$connMedical ->query($limpezaConvenios);
$connMedical ->query($limpezaPacientes);
$resetSequencialConv = "ALTER TABLE convenios AUTO_INCREMENT = 5;";
$resetSequencialPaci = "ALTER TABLE pacientes AUTO_INCREMENT = 2;";
$connMedical ->query($resetSequencialConv);
$connMedical ->query($resetSequencialPaci);


$h = fopen("/home/gabriel/Downloads/migration-challenge-main/dados_sistema_legado/20210512_pacientes.csv","r") 
or die("Não foi possivel abrir o arquivo csv") ;

$cabeçalho = true;

while (($dados = fgetcsv($h, 1000, ";"))!== false)

{

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
  $idConvenio = intval($dados[8]);
  $convenio = $dados[9];
  if ($sexo === 'M') {
    $sexo = 'Masculino';
  } else {
    $sexo = 'Feminino';
  } 
  //inserindo convenios
  $insertConvenio = "INSERT INTO convenios (nome, descricao)VALUES ('$convenio','$convenio')";
  $connMedical->query($insertConvenio) or die("Ocorreu um erro ao inserir o convenio");

  //buscando o id salvo dos convenios para inserir nos pacientes
  $selectConvenio = "SELECT id FROM convenios WHERE nome = '$convenio'";
  $resultQuery = $connMedical->query($selectConvenio) or die("Ocorreu um erro ao selecionar o convenio");
  $linhas = $resultQuery->fetch_assoc();
  $idSalvo = $linhas['id'];
 
  //inserindo os pacientes
  $insertPaciente = $connMedical->prepare("INSERT INTO pacientes (nome, sexo, nascimento, cpf, rg, id_convenio, cod_referencia) 
  VALUES (?, ?, ?, ?, ?, ?, ?)");
  $insertPaciente->bind_param("sssssii",$nome, $sexo, $dataFormatada, $cpf, $rg, $idSalvo,$codReferencia);
  $insertPaciente->execute() or die("Ocorreu um erro ao inserir o paciente");

}
fclose($h);


// Encerrando as conexões:
$connMedical->close();
//$connTemp->close();

// Informações de Fim da Migração:
echo "Fim da Migração: " . dateNow() . ".\n";

