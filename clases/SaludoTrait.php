<?php

require_once __DIR__ . '/Translator.php';

trait SaludoTrait {
    public function generarSaludo(): string {
        return Translator::get('greeting');
    }
}

?>
