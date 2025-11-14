<?php

class FormValidator
{
    private $data;
    private $errors = [];
    private $inscriptorDAO;

    public function __construct(array $data, InscriptorDAO $inscriptorDAO)
    {
        $this->data = $data;
        $this->inscriptorDAO = $inscriptorDAO;
    }

    public function validate()
    {
        $this->validateNombre();
        $this->validateApellido();
        $this->validateEdad();
        $this->validateSexo();
        $this->validatePaisResidencia();
        $this->validateNacionalidad();
        $this->validateEmail();
        $this->validateCelular();
        $this->validateTemas();
        $this->validateFechaInscripcion();

        return empty($this->errors);
    }

    private function validateNombre()
    {
        if (empty($this->data['nombre'])) {
            $this->addError('nombre', "El nombre es obligatorio.");
        }
    }

    private function validateApellido()
    {
        if (empty($this->data['apellido'])) {
            $this->addError('apellido', "El apellido es obligatorio.");
        }
    }

    private function validateEdad()
    {
        $edad = filter_var($this->data['edad'], FILTER_VALIDATE_INT);
        if (!$edad || $this->data['edad'] < 18 || $this->data['edad'] > 99) {
            $this->addError('edad', "La edad debe ser un número entre 18 y 99.");
        }
    }

    private function validateEmail()
    {
        if (empty($this->data['correo'])) {
            $this->addError('correo', "El correo electrónico es obligatorio.");
        } elseif (!filter_var($this->data['correo'], FILTER_VALIDATE_EMAIL)) {
            $this->addError('correo', "El formato del correo electrónico no es válido.");
        } elseif ($this->inscriptorDAO->emailExists($this->data['correo'])) {
            $this->addError('correo', "Este correo electrónico ya está registrado.");
        }
    }

    private function validateCelular()
    {
        // Celular is optional, only validate if provided
        if (!empty($this->data['celular'])) {
            $celular = trim($this->data['celular']);
            // Basic validation for phone number format (e.g., allow digits, spaces, hyphens, plus sign)
            if (!preg_match("/^[0-9\s\-\+]{7,20}$/", $celular)) {
                $this->addError('celular', "El formato del número de celular no es válido.");
            } elseif ($this->inscriptorDAO->celularExists($celular)) {
                $this->addError('celular', "Este número de celular ya está registrado.");
            }
        }
    }

    private function validateSexo()
    {
        if (!isset($this->data['sexo']) || !in_array($this->data['sexo'], ['Masculino', 'Femenino', 'Otro'])) {
            $this->addError('sexo', "El sexo seleccionado no es válido.");
        }
    }

    private function validatePaisResidencia()
    {
        if (empty($this->data['pais_residencia']) || !filter_var($this->data['pais_residencia'], FILTER_VALIDATE_INT)) {
            $this->addError('pais_residencia', "Debe seleccionar un país de residencia válido.");
        }
    }

    private function validateNacionalidad()
    {
        if (empty($this->data['nacionalidad']) || !filter_var($this->data['nacionalidad'], FILTER_VALIDATE_INT)) {
            $this->addError('nacionalidad', "Debe seleccionar una nacionalidad válida.");
        }
    }

    private function validateTemas()
    {
        if (isset($this->data['temas']) && is_array($this->data['temas'])) {
            foreach ($this->data['temas'] as $tema_id) {
                if (!filter_var($tema_id, FILTER_VALIDATE_INT)) {
                    $this->addError('temas', "Uno o más temas de interés seleccionados no son válidos.");
                    break;
                }
            }
        }
    }

    private function validateFechaInscripcion()
    {
        if (empty($this->data['fecha_inscripcion']) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $this->data['fecha_inscripcion'])) {
            $this->addError('fecha_inscripcion', "El formato de la fecha de inscripción no es válido.");
        }
    }

    private function addError($key, $message)
    {
        $this->errors[$key] = $message;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSanitizedData()
    {
        return [
            'nombre' => isset($this->data['nombre']) ? strtoupper(trim($this->data['nombre'])) : '',
            'apellido' => isset($this->data['apellido']) ? strtoupper(trim($this->data['apellido'])) : '',
            'edad' => isset($this->data['edad']) ? filter_var($this->data['edad'], FILTER_VALIDATE_INT) : null,
            'sexo' => isset($this->data['sexo']) ? $this->data['sexo'] : '',
            'pais_residencia_id' => isset($this->data['pais_residencia']) ? filter_var($this->data['pais_residencia'], FILTER_VALIDATE_INT) : null,
            'nacionalidad_id' => isset($this->data['nacionalidad']) ? filter_var($this->data['nacionalidad'], FILTER_VALIDATE_INT) : null,
            'observaciones' => isset($this->data['observaciones']) ? trim($this->data['observaciones']) : '',
            'fecha_inscripcion' => isset($this->data['fecha_inscripcion']) ? $this->data['fecha_inscripcion'] : date('Y-m-d'),
            'correo' => isset($this->data['correo']) ? trim($this->data['correo']) : '',
            'celular' => isset($this->data['celular']) ? trim($this->data['celular']) : '',
            'temas' => isset($this->data['temas']) ? array_map('intval', $this->data['temas']) : [],
        ];
    }
}

?>
