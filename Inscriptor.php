<?php

class Inscriptor
{
    public $id;
    public $nombre;
    public $apellido;
    public $edad;
    public $sexo;
    public $pais_residencia_id;
    public $nacionalidad_id;
    public $observaciones;
    public $fecha_registro;
    public $temas_interes_ids = []; // Array of TemaInteres IDs
    public $correo;
    public $celular;

    // Properties for displaying details in reports (not part of initial save)
    public $pais_residencia_nombre;
    public $nacionalidad_nombre;
    public $temas_interes_nombres = []; // Array of TemaInteres names

    public function __construct(
        $id = null,
        $nombre,
        $apellido,
        $correo,
        $celular,
        $edad,
        $sexo,
        $pais_residencia_id,
        $nacionalidad_id,
        $observaciones,
        $fecha_registro,
        array $temas_interes_ids = []
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->correo = $correo;
        $this->celular = $celular;
        $this->edad = $edad;
        $this->sexo = $sexo;
        $this->pais_residencia_id = $pais_residencia_id;
        $this->nacionalidad_id = $nacionalidad_id;
        $this->observaciones = $observaciones;
        $this->fecha_registro = $fecha_registro;
        $this->temas_interes_ids = $temas_interes_ids;
    }
}

?>
