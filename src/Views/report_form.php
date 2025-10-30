<?php
// Garantir que as variáveis existam
$error = $error ?? null;
$result = $result ?? null;
$intervals = $intervals ?? null;
$search_type = $search_type ?? 'subject';
$assunto = $assunto ?? '';
$data_inicio = $data_inicio ?? '';
$data_fim = $data_fim ?? '';
$remove_defaults = $remove_defaults ?? 'no';
$test_recipients = $test_recipients ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de E-mails do Dynamics 365</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        :root {
            --cor-principal: #1aa97f;
            --cor-principal-hover: #168a68;
            --cor-fundo: #f4f7f6;
            --cor-texto: #333;
            --cor-botao-export: #6c757d;
            --cor-botao-export-hover: #5a6268;
            --cor-ok: #28a745;
            --cor-medio: #ffc107;
            --cor-critico: #fd7e14;
            --cor-urgente: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-gradient {
            background: linear-gradient(135deg, var(--cor-principal) 0%, #1cd09b 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(26, 169, 127, 0.3);
        }

        .header-gradient h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .section-card {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            color: var(--cor-principal);
            font-weight: 600;
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            font-size: 1.35rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.75rem;
        }

        .section-title i {
            font-size: 1.3rem;
            margin-right: 0.75rem;
        }

        .search-type-container {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            width: 20px;
            height: 20px;
            margin-right: 0.5rem;
            cursor: pointer;
        }

        .radio-option label {
            cursor: pointer;
            font-weight: 600;
            margin: 0;
        }

        .search-fields {
            display: none;
        }

        .search-fields.active {
            display: block;
        }

        .filter-options {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .filter-options h3 {
            color: #1976d2;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .filter-options h3 i {
            margin-right: 0.5rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 0.75rem;
            cursor: pointer;
        }

        .checkbox-group label {
            cursor: pointer;
            margin: 0;
            font-weight: 600;
        }

        .conditional-field {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #ff9800;
        }

        .conditional-field.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #555;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--cor-principal);
            box-shadow: 0 0 0 3px rgba(26, 169, 127, 0.15);
        }

        .form-help {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.4rem;
            display: block;
        }

        .btn {
            font-weight: 600;
            padding: 1rem 2rem;
            font-size: 1.05rem;
            border-radius: 8px;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-gerar {
            background-color: var(--cor-principal);
            color: white;
            width: 100%;
            box-shadow: 0 4px 15px rgba(26, 169, 127, 0.3);
        }

        .btn-gerar:hover:not(:disabled) {
            background-color: var(--cor-principal-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 169, 127, 0.4);
        }

        .btn-export {
            background-color: var(--cor-botao-export);
            color: white;
            padding: 0.7rem 1.5rem;
            font-size: 0.95rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .btn-export:hover {
            background-color: var(--cor-botao-export-hover);
            transform: translateY(-2px);
        }

        .btn-export.excel { background-color: #217346; }
        .btn-export.pdf { background-color: #d32f2f; }
        .btn-export.xml { background-color: #ff9800; }

        .alert {
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid;
            display: flex;
            align-items: start;
        }

        .alert i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
            margin-top: 0.1rem;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .intervals-card {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
            border: 2px solid #ffc107;
            margin-bottom: 2rem;
        }

        .intervals-table {
            width: 100%;
            margin-top: 1rem;
        }

        .intervals-table th {
            background-color: rgba(0, 0, 0, 0.05);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .intervals-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .badge-danger {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
        }

        .metric-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
        }

        .metric-row:last-child {
            border-bottom: none;
        }

        .metric-label {
            font-weight: 600;
            color: #495057;
        }

        .metric-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--cor-principal);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.95rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        #loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .spinner {
            border: 8px solid #f3f3f3;
            border-top: 8px solid var(--cor-principal);
            border-radius: 50%;
            width: 70px;
            height: 70px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .group-separator {
            margin: 3rem 0;
            border-top: 3px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .search-type-container {
                flex-direction: column;
                gap: 1rem;
            }
            .btn-export {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div id="loader">
        <div class="spinner"></div>
        <p style="margin-top: 25px; font-weight: 600; font-size: 1.1rem;">
            Buscando dados na API do Dynamics... Aguarde.
        </p>
    </div>

    <div class="container">
        <div class="header-gradient">
            <h1><i class="fas fa-chart-line"></i> Relatório de Engajamento</h1>
            <p>Análise completa de campanhas de e-mail do Dynamics 365</p>
        </div>

        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-search"></i>
                Filtros da Pesquisa
            </h2>
            
            <form method="POST" action="" id="reportForm">
                <!-- Radio buttons para tipo de busca -->
                <div class="search-type-container">
                    <div class="radio-option">
                        <input 
                            type="radio" 
                            id="search_subject" 
                            name="search_type" 
                            value="subject"
                            <?php echo ($search_type === 'subject') ? 'checked' : ''; ?>
                        >
                        <label for="search_subject">🔍 Buscar por Assunto</label>
                    </div>
                    <div class="radio-option">
                        <input 
                            type="radio" 
                            id="search_date" 
                            name="search_type" 
                            value="date"
                            <?php echo ($search_type === 'date') ? 'checked' : ''; ?>
                        >
                        <label for="search_date">📅 Buscar por Intervalo de Data</label>
                    </div>
                </div>

                <!-- Campos de busca por ASSUNTO -->
                <div id="fields_subject" class="search-fields <?php echo ($search_type === 'subject') ? 'active' : ''; ?>">
                    <div class="form-group">
                        <label for="assunto">
                            <i class="fas fa-envelope"></i> Assunto(s) do E-mail
                        </label>
                        <input 
                            type="text" 
                            id="assunto" 
                            name="assunto" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($assunto, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Ex: Newsletter;; Campanha Black Friday"
                        >
                        <small class="form-help">
                            <i class="fas fa-info-circle"></i> 
                            Separe múltiplos assuntos por <strong>;;</strong> (ponto e vírgula duplo).
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="data_inicio_subject">
                            <i class="fas fa-calendar-alt"></i> Enviados a partir de:
                        </label>
                        <input 
                            type="date" 
                            id="data_inicio_subject" 
                            name="data_inicio" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($data_inicio, ENT_QUOTES, 'UTF-8'); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                        >
                    </div>
                </div>

                <!-- Campos de busca por DATA -->
                <div id="fields_date" class="search-fields <?php echo ($search_type === 'date') ? 'active' : ''; ?>">
                    <div class="form-group">
                        <label for="data_inicio_date">
                            <i class="fas fa-calendar-alt"></i> Data de Início:
                        </label>
                        <input 
                            type="date" 
                            id="data_inicio_date" 
                            name="data_inicio" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($data_inicio, ENT_QUOTES, 'UTF-8'); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="data_fim">
                            <i class="fas fa-calendar-check"></i> Data de Término:
                        </label>
                        <input 
                            type="date" 
                            id="data_fim" 
                            name="data_fim" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($data_fim, ENT_QUOTES, 'UTF-8'); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                        >
                        <small class="form-help">
                            <i class="fas fa-info-circle"></i> 
                            Remetente fixo: sucessoalvarista@fecap.br | Limite: 1 ano
                        </small>
                    </div>
                </div>

                <!-- NOVO: Filtros Adicionais -->
                <div class="filter-options">
                    <h3>
                        <i class="fas fa-filter"></i>
                        Filtros Adicionais
                    </h3>

                    <!-- Remover E-mails Padrão -->
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="remove_defaults" 
                            name="remove_defaults" 
                            value="yes"
                            <?php echo ($remove_defaults === 'yes') ? 'checked' : ''; ?>
                        >
                        <label for="remove_defaults">
                            🔄 Remover e-mails padrão/automáticos do relatório
                        </label>
                    </div>
                    <small class="form-help" style="margin-left: 2.5rem; display: block; margin-bottom: 1rem;">
                        <i class="fas fa-info-circle"></i> 
                        Remove e-mails automáticos definidos em <code>config/default_emails.php</code>
                    </small>

                    <!-- Remover Destinatários de Teste -->
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="enable_test_filter" 
                            <?php echo (!empty($test_recipients)) ? 'checked' : ''; ?>
                        >
                        <label for="enable_test_filter">
                            🧪 Remover destinatários de teste das métricas
                        </label>
                    </div>

                    <div id="test_recipients_field" class="conditional-field <?php echo (!empty($test_recipients)) ? 'active' : ''; ?>">
                        <label for="test_recipients">
                            <i class="fas fa-users"></i> E-mails de Teste (separados por vírgula):
                        </label>
                        <input 
                            type="text" 
                            id="test_recipients" 
                            name="test_recipients" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($test_recipients, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="teste@exemplo.com, teste2@exemplo.com"
                        >
                        <small class="form-help">
                            <i class="fas fa-exclamation-triangle"></i> 
                            E-mails informados <strong>não entrarão</strong> no cálculo das métricas
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn btn-gerar" id="btnGerar">
                    <i class="fas fa-chart-bar"></i>
                    Gerar Relatório
                </button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="section-card">
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?php echo $error; ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($result): ?>
            <!-- Card de Análise de Intervalos -->
            <?php if (isset($intervals) && !empty($intervals)): ?>
                <div class="section-card intervals-card">
                    <h2 class="section-title" style="color: #856404;">
                        <i class="fas fa-clock"></i>
                        Análise de Duração dos Disparos
                    </h2>
                    <p style="margin-bottom: 1rem; color: #856404;">
                        <strong>ℹ️ Informação:</strong> Mostra a duração total de cada campanha (do primeiro ao último e-mail enviado).
                    </p>

                    <table class="intervals-table">
                        <thead>
                            <tr>
                                <th>Assunto</th>
                                <th>Início do Disparo</th>
                                <th>Término do Disparo</th>
                                <th>Intervalo (Duração)</th>
                                <th>Grau de Perigo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($intervals as $interval): ?>
                                <tr>
                                    <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($interval['assunto'], 0, 40), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (strlen($interval['assunto']) > 40) echo '...'; ?>
                                    </td>
                                    <td style="font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($interval['inicio'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td style="font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($interval['termino'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td style="font-weight: 600; font-size: 1.1rem;">
                                        <?php echo htmlspecialchars($interval['intervalo_formatado'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <span class="badge-danger" style="background-color: <?php echo $interval['cor']; ?>;">
                                            <?php 
                                            $icon = [
                                                'OK' => '✓',
                                                'Médio' => '⚠',
                                                'Crítico' => '⚠⚠',
                                                'Urgente' => '🔴'
                                            ];
                                            echo $icon[$interval['grau_perigo']] . ' ' . htmlspecialchars($interval['grau_perigo'], ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.5); border-radius: 8px;">
                        <h4 style="margin: 0 0 0.5rem 0;">📋 Legenda:</h4>
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                            <span><span class="badge-danger" style="background-color: var(--cor-ok);">✓ OK</span> Menos de 30min</span>
                            <span><span class="badge-danger" style="background-color: var(--cor-medio);">⚠ Médio</span> 30min - 1h</span>
                            <span><span class="badge-danger" style="background-color: var(--cor-critico);">⚠⚠ Crítico</span> 1h - 5h</span>
                            <span><span class="badge-danger" style="background-color: var(--cor-urgente);">🔴 Urgente</span> Mais de 5h</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Botões de Exportação -->
            <div class="section-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <h2 class="section-title" style="margin: 0;">
                        <i class="fas fa-chart-pie"></i>
                        Resultados Encontrados
                    </h2>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="?export=csv" class="btn btn-export">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                        <a href="?export=excel" class="btn btn-export excel">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                        <a href="?export=pdf" class="btn btn-export pdf">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <a href="?export=xml" class="btn btn-export xml">
                            <i class="fas fa-file-code"></i> XML
                        </a>
                    </div>
                </div>
            </div>

            <!-- Resultados por Assunto -->
            <?php foreach ($result as $subject => $report): ?>
                <div class="section-card">
                    <h3 class="section-title">
                        <i class="fas fa-mail-bulk"></i>
                        <?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?>
                    </h3>

                    <div class="metric-card">
                        <h4 style="color: #495057; margin-bottom: 1rem; font-size: 1.1rem;">
                            <i class="fas fa-chart-line"></i> Métricas Principais
                        </h4>
                        
                        <?php foreach ($report['metricas'] as $metrica => $valor): ?>
                            <div class="metric-row">
                                <span class="metric-label"><?php echo htmlspecialchars($metrica, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="metric-value">
                                    <?php 
                                    if (strpos($metrica, 'Taxa') !== false || strpos($metrica, 'CTR') !== false || strpos($metrica, '%') !== false) {
                                        echo number_format($valor, 2, ',', '.') . '%';
                                    } elseif (is_numeric($valor)) {
                                        echo number_format($valor, 0, ',', '.');
                                    } else {
                                        echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h4 style="color: #495057; margin: 2rem 0 1rem; font-size: 1.05rem;">
                        <i class="fas fa-list-ul"></i> Detalhes por Status do E-mail
                    </h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th style="text-align: right;">Quantidade</th>
                                <th style="text-align: right;">Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = $report['metricas']['Total de Envios'];
                            foreach ($report['contadoresEmail'] as $status => $count): 
                                if ($count > 0):
                                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?php echo number_format($count, 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: right; color: var(--cor-principal); font-weight: 600;">
                                        <?php echo number_format($percentage, 2, ',', '.'); ?>%
                                    </td>
                                </tr>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>

                    <h4 style="color: #495057; margin: 2rem 0 1rem; font-size: 1.05rem;">
                        <i class="fas fa-info-circle"></i> Razão do Status
                    </h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th style="text-align: right;">Quantidade</th>
                                <th style="text-align: right;">Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($report['contadoresHeader'] as $status => $count): 
                                if ($count > 0):
                                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?php echo number_format($count, 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: right; color: var(--cor-principal); font-weight: 600;">
                                        <?php echo number_format($percentage, 2, ',', '.'); ?>%
                                    </td>
                                </tr>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>

                <?php if (next($result) !== false): ?>
                    <div class="group-separator"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reportForm');
            const loader = document.getElementById('loader');
            const btnGerar = document.getElementById('btnGerar');
            
            const radioSubject = document.getElementById('search_subject');
            const radioDate = document.getElementById('search_date');
            const fieldsSubject = document.getElementById('fields_subject');
            const fieldsDate = document.getElementById('fields_date');

            function toggleSearchFields() {
                if (radioSubject.checked) {
                    fieldsSubject.classList.add('active');
                    fieldsDate.classList.remove('active');
                    
                    document.getElementById('assunto').disabled = false;
                    document.getElementById('data_inicio_subject').disabled = false;
                    document.getElementById('data_inicio_date').disabled = true;
                    document.getElementById('data_fim').disabled = true;
                    
                } else if (radioDate.checked) {
                    fieldsDate.classList.add('active');
                    fieldsSubject.classList.remove('active');
                    
                    document.getElementById('assunto').disabled = true;
                    document.getElementById('data_inicio_subject').disabled = true;
                    document.getElementById('data_inicio_date').disabled = false;
                    document.getElementById('data_fim').disabled = false;
                }
            }

            radioSubject.addEventListener('change', toggleSearchFields);
            radioDate.addEventListener('change', toggleSearchFields);
            toggleSearchFields();

            // Toggle para campo de destinatários de teste
            const enableTestFilter = document.getElementById('enable_test_filter');
            const testRecipientsField = document.getElementById('test_recipients_field');
            const testRecipientsInput = document.getElementById('test_recipients');

            if (enableTestFilter && testRecipientsField) {
                function toggleTestField() {
                    if (enableTestFilter.checked) {
                        testRecipientsField.classList.add('active');
                        testRecipientsInput.disabled = false;
                    } else {
                        testRecipientsField.classList.remove('active');
                        testRecipientsInput.disabled = true;
                        testRecipientsInput.value = '';
                    }
                }

                enableTestFilter.addEventListener('change', toggleTestField);
                toggleTestField();
            }

            if (form) {
                form.addEventListener('submit', function(e) {
                    const searchType = document.querySelector('input[name="search_type"]:checked');
                    
                    if (!searchType) {
                        e.preventDefault();
                        alert('Selecione o tipo de busca');
                        return false;
                    }

                    const searchValue = searchType.value;
                    let isValid = false;
                    let errorMsg = '';
                    
                    if (searchValue === 'subject') {
                        const assunto = document.getElementById('assunto').value.trim();
                        const data = document.getElementById('data_inicio_subject').value.trim();
                        
                        if (!assunto) {
                            errorMsg = 'Por favor, informe o assunto do e-mail';
                        } else if (!data) {
                            errorMsg = 'Por favor, informe a data de início';
                        } else {
                            isValid = true;
                        }
                    } else {
                        const dataInicio = document.getElementById('data_inicio_date').value.trim();
                        const dataFim = document.getElementById('data_fim').value.trim();
                        
                        if (!dataInicio) {
                            errorMsg = 'Por favor, informe a data de início';
                        } else if (!dataFim) {
                            errorMsg = 'Por favor, informe a data de término';
                        } else if (dataFim < dataInicio) {
                            errorMsg = 'Data de término deve ser posterior à data de início';
                        } else {
                            isValid = true;
                        }
                    }

                    if (isValid) {
                        loader.style.display = 'flex';
                        btnGerar.disabled = true;
                        btnGerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                        return true;
                    } else {
                        e.preventDefault();
                        alert(errorMsg);
                        return false;
                    }
                });
            }

            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.setAttribute('max', today);
            });
        });
    </script>
</body>
</html>