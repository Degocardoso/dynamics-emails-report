<?php

/**
 * Configuração de Assuntos de E-mails Padrão/Automáticos
 *
 * IMPORTANTE: Este arquivo contém apenas ASSUNTOS de e-mails, NÃO destinatários.
 *
 * Os assuntos listados aqui são usados para filtrar e-mails automáticos/padrão
 * quando o usuário marca a opção "Remover e-mails padrão/automáticos" no formulário.
 *
 * Para filtrar DESTINATÁRIOS de teste, use o campo "Destinatários de teste" no formulário.
 */

return [
    'default_subjects' => [
        // Adicione aqui os assuntos de e-mails automáticos/padrão
        'ASA | Seu Atendimento começa agora!',
        'ASA | Você é o próximo da fila!',
        'ASA | Sua senha de atendimento presencial',
        'ASA | Atendimento cancelado!',
        'RE:',
    ],
];