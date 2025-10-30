<?php

namespace App\Validators;

class ReportDateValidator
{
    private $errors = [];

    /**
     * Valida busca por intervalo de datas
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        // Valida data início
        if (empty($data['data_inicio'])) {
            $this->errors['data_inicio'] = 'Data de início é obrigatória.';
        } elseif (!$this->isValidDate($data['data_inicio'])) {
            $this->errors['data_inicio'] = 'Data de início inválida.';
        }

        // Valida data fim
        if (empty($data['data_fim'])) {
            $this->errors['data_fim'] = 'Data de término é obrigatória.';
        } elseif (!$this->isValidDate($data['data_fim'])) {
            $this->errors['data_fim'] = 'Data de término inválida.';
        }

        // Valida intervalo
        if (empty($this->errors)) {
            $inicio = new \DateTime($data['data_inicio']);
            $fim = new \DateTime($data['data_fim']);
            $now = new \DateTime();

            if ($fim < $inicio) {
                $this->errors['data_fim'] = 'Data de término deve ser posterior à data de início.';
            }

            if ($inicio > $now) {
                $this->errors['data_inicio'] = 'Data de início não pode ser futura.';
            }

            // Limita a 1 ano
            $diff = $inicio->diff($fim);
            if ($diff->days > 365) {
                $this->errors['intervalo'] = 'Intervalo máximo permitido: 1 ano.';
            }
        }

        return empty($this->errors);
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}