<?php

namespace App\Validators;

use Respect\Validation\Validator as v;

class ReportRequestValidator
{
    private $errors = [];
    
    // NOVO: Separador customizado
    const SUBJECT_SEPARATOR = ';;';

    /**
     * Valida os dados do formulário de relatório
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        // Valida assunto
        if (!$this->validateSubjects($data['assunto'] ?? '')) {
            $this->errors['assunto'] = 'Por favor, forneça pelo menos um assunto válido.';
        }

        // Valida data de início
        if (!$this->validateStartDate($data['data_inicio'] ?? '')) {
            $this->errors['data_inicio'] = 'Data de início inválida. Use o formato AAAA-MM-DD.';
        }

        return empty($this->errors);
    }

    /**
     * Valida e limpa assuntos
     */
    private function validateSubjects(string $subjects): bool
    {
        if (empty(trim($subjects))) {
            return false;
        }

        // Verifica tamanho máximo
        if (strlen($subjects) > 1000) {
            return false;
        }

        // Verifica se há pelo menos um assunto válido após split
        $subjectsArray = array_map('trim', explode(self::SUBJECT_SEPARATOR, $subjects));
        $validSubjects = array_filter($subjectsArray, function($subject) {
            return !empty($subject) && strlen($subject) >= 3;
        });

        return count($validSubjects) > 0;
    }

    /**
     * Valida data de início
     */
    private function validateStartDate(string $date): bool
    {
        if (empty($date)) {
            return false;
        }

        try {
            // Valida formato
            if (!v::date('Y-m-d')->validate($date)) {
                return false;
            }

            $dateObj = new \DateTime($date);
            $now = new \DateTime();
            
            // Data não pode ser futura
            if ($dateObj > $now) {
                return false;
            }

            // Data não pode ser muito antiga (ex: mais de 2 anos)
            $twoYearsAgo = (clone $now)->modify('-2 years');
            if ($dateObj < $twoYearsAgo) {
                return false;
            }

            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retorna os erros de validação
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna primeira mensagem de erro
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Sanitiza e retorna array de assuntos válidos
     */
    public static function sanitizeSubjects(string $subjects): array
    {
        $subjectsArray = explode(self::SUBJECT_SEPARATOR, $subjects);
        $cleaned = [];
        
        foreach ($subjectsArray as $subject) {
            $subject = trim($subject);
            
            // Remove caracteres potencialmente perigosos mantendo acentuação
            $subject = preg_replace('/[^\w\s\-.:,;!?()\[\]áéíóúàèìòùâêîôûãõçÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕÇ]/u', '', $subject);
            
            // Limita tamanho
            $subject = substr($subject, 0, 200);
            
            if (!empty($subject) && strlen($subject) >= 3) {
                $cleaned[] = $subject;
            }
        }
        
        return $cleaned;
    }
}