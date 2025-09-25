<?php
class LocalConector{
    private $host = "127.0.0.1:3306";
    private $usuario = "u909553968_UserRootKanban";
    private $clave = "Grammer2025";
    private $db = "u909553968_RootKanban";
    public $conexion;
    public function conectar(){
        $con = mysqli_connect($this->host,$this->usuario,$this->clave,$this->db);
        return $con;
    }
}
?>
