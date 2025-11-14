<?php

class TemaInteres
{
    public $id;
    public $tema;

    public function __construct($id, $tema)
    {
        $this->id = $id;
        $this->tema = $tema;
    }
}

?>
