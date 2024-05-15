<?php

class Paciente
{
    private $id;
    private $codReferencia;
    private $nome;
    private $dataNascimento;
    private $cpf;
    private $rg;
    private $sexo;
    private $convenio;


    public function __construct($id, $codReferencia, $nome, $dataNascimento, $cpf, $rg, $sexo, $convenio)
    {
        $this->id = $id;
        $this->codReferencia = $codReferencia;
        $this->nome = $nome;
        $this->dataNascimento = $dataNascimento;
        $this->cpf = $cpf;
        $this->rg = $rg;
        $this->sexo = $sexo;
        $this->convenio = $convenio;
    }

    public function getid()
    {
        return $this->id;
    }

    public function setid($conexao)
    {
        $this->id = $this->gerarId($conexao);
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function getdataNascimento()
    {
        return $this->dataNascimento;
    }

    public function setdataNascimento($dataNascimento)
    {
        $this->dataNascimento = $dataNascimento;
    }

    public function getcpf()
    {
        return $this->cpf;
    }

    public function setcpf($cpf)
    {
        $this->cpf = $cpf;
    }

    public function getrg()
    {
        return $this->rg;
    }

    public function setrg($rg)
    {
        $this->rg = $rg;
    }

    public function getsexo()
    {
        return $this->sexo;
    }

    public function setsexo($sexo)
    {
        $this->sexo = $sexo;
    }

    public function getconvenio()
    {
        return $this->convenio;
    }

    public function setconvenio($convenio)
    {
        $this->convenio = $convenio;
    }

    public function getcodReferencia()
    {
        return $this->codReferencia;
    }

    public function setcodReferencia($codReferencia)
    {
        $this->codReferencia = $codReferencia;
    }

    public function gerarId($conexao)
    {
        $idInicial = 1;

        $buscarIdSalvo = "SELECT MAX(id) as ultimoID FROM pacientes;";
        $resultado = $conexao->query($buscarIdSalvo);

        if ($resultado) {
            $linhasRetornadas = $resultado->fetch_assoc();
            $idInicial = $linhasRetornadas['ultimoID'];
        }

        $proximoId = $idInicial + 1;


        return $proximoId;
    }
};
