<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de custos</title>
    {{--
        Relatório em PDF gerado pelo DomPDF (RelatorioPdfController).
        O DomPDF entende HTML/CSS simples (sem flexbox/grid e sem JavaScript):
        o layout usa tabelas e os gráficos são barras desenhadas em CSS.
    --}}
    <style>
        @page { margin: 27mm 14mm 19mm 14mm; }

        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; line-height: 1.4; color: #3f3f46; }
        table { width: 100%; border-collapse: collapse; }
        .num { text-align: right; white-space: nowrap; }
        .suave { color: #71717a; }
        .forte { font-weight: bold; color: #18181b; }

        /* Cabeçalho e rodapé repetidos em todas as páginas */
        .topo { position: fixed; top: -20mm; left: 0; right: 0; height: 13mm; border-bottom: 0.75pt solid #e4e4e7; }
        .topo td { vertical-align: middle; }
        .topo .logo { width: 93pt; height: 28pt; }
        .topo .sistema { font-size: 9.5pt; font-weight: bold; color: #18181b; padding-left: 7pt; }
        .topo .sistema span { display: block; font-size: 7pt; font-weight: normal; color: #71717a; }
        .topo .documento { text-align: right; font-size: 7.5pt; color: #71717a; }
        .rodape { position: fixed; bottom: -13mm; left: 0; right: 0; height: 8mm; border-top: 0.75pt solid #e4e4e7; padding-top: 5pt; font-size: 7pt; color: #a1a1aa; }

        /* Capa */
        .capa { background: #0a4e35; border-radius: 8pt; padding: 16pt 18pt; color: #ffffff; }
        .capa td { vertical-align: bottom; }
        .capa .sobretitulo { font-size: 7pt; font-weight: bold; letter-spacing: 1.6pt; text-transform: uppercase; color: #b9dca0; }
        .capa .titulo { margin-top: 5pt; font-size: 20pt; font-weight: bold; color: #ffffff; }
        .capa .subtitulo { margin-top: 3pt; font-size: 8.5pt; color: #d4e5cb; }
        .capa .total-rotulo { text-align: right; font-size: 7.5pt; color: #d4e5cb; }
        .capa .total-valor { text-align: right; font-size: 19pt; font-weight: bold; color: #ffffff; }

        /* Cartões (filtros e indicadores): cada célula contém um bloco arredondado */
        .cartoes { margin-top: 10pt; }
        .cartoes td { padding: 0 3pt; vertical-align: top; }
        .cartoes td:first-child { padding-left: 0; }
        .cartoes td:last-child { padding-right: 0; }
        .filtro { background: #f4f4f5; border-radius: 6pt; padding: 7pt 9pt; }
        .indicador { border: 0.75pt solid #e4e4e7; border-radius: 6pt; padding: 8pt 9pt; }
        .indicador.destaque { background: #f0fdf4; border-color: #bbf7d0; }
        .rotulo { font-size: 6.5pt; font-weight: bold; letter-spacing: 0.7pt; text-transform: uppercase; color: #71717a; }
        .valor { margin-top: 2pt; font-size: 9pt; font-weight: bold; color: #18181b; }
        .valor.grande { font-size: 12pt; }
        .detalhe { margin-top: 1pt; font-size: 6.5pt; color: #71717a; }

        /* Seções */
        .secao { margin-top: 18pt; page-break-inside: avoid; }
        .secao h2 { margin: 0; font-size: 11pt; color: #18181b; }
        .secao h2 .marcador { display: inline-block; width: 3pt; height: 10pt; margin-right: 6pt; background: #16a34a; border-radius: 1pt; }
        .secao .descricao { margin: 2pt 0 9pt 9pt; font-size: 7.5pt; color: #71717a; }
        .secao-lista { margin-top: 20pt; }
        .lado-a-lado td.metade { width: 50%; vertical-align: top; }
        .lado-a-lado td.metade:first-child { padding-right: 9pt; }
        .lado-a-lado td.metade:last-child { padding-left: 9pt; }

        /* Gráfico: barra única empilhada (composição) */
        .empilhada td { height: 14pt; }
        .empilhada td + td { border-left: 2pt solid #ffffff; }
        .legenda { margin-top: 7pt; }
        .legenda td { width: 33.3%; vertical-align: top; }
        .quadrado { display: inline-block; width: 7pt; height: 7pt; border-radius: 2pt; margin-right: 4pt; vertical-align: 0; }

        /* Gráfico: colunas empilhadas (evolução mensal) */
        .colunas td.coluna { height: 150px; padding: 0 4pt; vertical-align: bottom; border-bottom: 0.75pt solid #d4d4d8; }
        .colunas .coluna-total { margin-bottom: 2pt; text-align: center; font-size: 6pt; color: #52525b; white-space: nowrap; }
        .colunas td.coluna-rotulo { padding-top: 3pt; text-align: center; font-size: 6.5pt; color: #71717a; }

        /* Gráfico: barras horizontais */
        .barras td { padding: 3pt 0; vertical-align: middle; }
        .barras .barra-rotulo { width: 30%; padding-right: 6pt; font-size: 7.5pt; color: #3f3f46; }
        .barras .barra-valor { width: 24%; padding-left: 6pt; font-size: 7.5pt; font-weight: bold; color: #18181b; }
        .trilho { background: #f4f4f5; border-radius: 3pt; }
        .barra { height: 9pt; background: #2a78d6; border-radius: 3pt; }

        /* Tabelas de dados */
        .dados th { padding: 5pt 5pt; background: #f4f4f5; border-bottom: 0.75pt solid #e4e4e7; text-align: left; font-size: 6.5pt; font-weight: bold; letter-spacing: 0.5pt; text-transform: uppercase; color: #52525b; }
        .dados th.num { text-align: right; }
        .dados td { padding: 4.5pt 5pt; border-bottom: 0.75pt solid #f4f4f5; font-size: 7.5pt; vertical-align: top; }
        .dados tbody tr:nth-child(even) td { background: #fafafa; }
        .dados tfoot td { padding: 6pt 5pt; border-top: 1pt solid #d4d4d8; border-bottom: none; font-weight: bold; color: #18181b; background: #ffffff; }
        .dados .subtexto { display: block; font-size: 6.5pt; color: #a1a1aa; }

        /* Metodologia */
        .nota { margin-top: 16pt; padding: 9pt 11pt; border: 0.75pt solid #e4e4e7; border-radius: 6pt; background: #fafafa; font-size: 7.5pt; color: #52525b; page-break-inside: avoid; }
        .nota strong { color: #18181b; }
        .vazio { padding: 14pt; border: 0.75pt dashed #d4d4d8; border-radius: 6pt; text-align: center; color: #71717a; }
    </style>
</head>
<body>
    <div class="topo">
        <table>
            <tr>
                <td style="width: 100pt;"><img class="logo" src="{{ $logo }}" alt="SCAA — Sistema de Controle de Atividades Agrícolas"></td>
                <td class="sistema">
                    <span>Apropriação de custos por propriedade e talhão</span>
                </td>
                <td class="documento">
                    Relatório de custos<br>
                    Gerado em {{ $geradoEm->format('d/m/Y') }} às {{ $geradoEm->format('H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="rodape">
        Valores calculados no lançamento de cada atividade · Usuário: {{ $usuario->name }}
    </div>

    {{-- Capa --}}
    <div class="capa">
        <table>
            <tr>
                <td>
                    <div class="sobretitulo">Relatório de custos</div>
                    <div class="titulo">{{ $filtrosDescritos['Propriedade'] === 'Todas' ? 'Todas as propriedades' : $filtrosDescritos['Propriedade'] }}</div>
                    <div class="subtitulo">
                        {{ $resumo['quantidade_atividades'] }} {{ $resumo['quantidade_atividades'] === 1 ? 'atividade' : 'atividades' }}
                        · {{ Number::format($resumo['area'], 2) }} ha de talhões com atividades
                    </div>
                </td>
                <td style="width: 38%;">
                    <div class="total-rotulo">Custo total</div>
                    <div class="total-valor">{{ Number::currency($resumo['custo_total']) }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Filtros aplicados --}}
    <table class="cartoes">
        <tr>
            @foreach ($filtrosDescritos as $rotulo => $valor)
                <td style="width: 25%;">
                    <div class="filtro">
                        <div class="rotulo">{{ $rotulo }}</div>
                        <div class="valor">{{ $valor }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- Indicadores --}}
    <table class="cartoes">
        <tr>
            @foreach ($fatias as $indice => $fatia)
                <td style="width: 20%;">
                    <div class="indicador">
                        <div class="rotulo"><span class="quadrado" style="background: {{ $fatia['cor'] }};"></span>{{ $fatia['rotulo'] }}</div>
                        <div class="valor">{{ $fatia['valor'] }}</div>
                        <div class="detalhe">{{ Number::format($fatia['percentual'], 1) }}% do custo total</div>
                    </div>
                </td>
            @endforeach
            <td style="width: 20%;">
                <div class="indicador">
                    <div class="rotulo">Custo total</div>
                    <div class="valor">{{ Number::currency($resumo['custo_total']) }}</div>
                    <div class="detalhe">{{ $resumo['quantidade_atividades'] }} atividade(s)</div>
                </div>
            </td>
            <td style="width: 20%;">
                <div class="indicador destaque">
                    <div class="rotulo">Custo por hectare</div>
                    <div class="valor">{{ $resumo['custo_por_hectare'] !== null ? Number::currency($resumo['custo_por_hectare']) : '—' }}</div>
                    <div class="detalhe">Sobre {{ Number::format($resumo['area'], 2) }} ha</div>
                </div>
            </td>
        </tr>
    </table>

    @if ($resumo['quantidade_atividades'] === 0)
        <div class="secao">
            <div class="vazio">Nenhuma atividade encontrada para os filtros selecionados.</div>
        </div>
    @else
        {{-- Composição --}}
        <div class="secao">
            <h2><span class="marcador"></span>Composição dos custos</h2>
            <div class="descricao">Participação de insumos, maquinário e mão de obra no custo total.</div>

            <table class="empilhada">
                <tr>
                    @foreach ($fatias as $fatia)
                        @if ($fatia['percentual'] > 0)
                            <td style="width: {{ $fatia['percentual'] }}%; background: {{ $fatia['cor'] }};"></td>
                        @endif
                    @endforeach
                </tr>
            </table>

            <table class="legenda">
                <tr>
                    @foreach ($fatias as $fatia)
                        <td>
                            <span class="quadrado" style="background: {{ $fatia['cor'] }};"></span>
                            <span class="forte">{{ $fatia['rotulo'] }}</span>
                            <span class="suave">· {{ $fatia['valor'] }} ({{ Number::format($fatia['percentual'], 1) }}%)</span>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>

        {{-- Evolução mensal --}}
        <div class="secao">
            <h2><span class="marcador"></span>Evolução mensal dos custos</h2>
            <div class="descricao">Custo das atividades de cada mês, empilhado por grupo de custo.</div>

            <table class="colunas">
                <tr>
                    @foreach ($colunasMensais as $coluna)
                        <td class="coluna" style="width: {{ round(100 / count($colunasMensais), 2) }}%;">
                            <div class="coluna-total">{{ $coluna['total'] }}</div>
                            @foreach ([2, 1, 0] as $grupo)
                                @if ($coluna['alturas'][$grupo] > 0)
                                    <div style="height: {{ $coluna['alturas'][$grupo] }}px; background: {{ $cores[$grupo] }};{{ $grupo > 0 ? ' border-bottom: 1.5pt solid #ffffff;' : '' }}"></div>
                                @endif
                            @endforeach
                        </td>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($colunasMensais as $coluna)
                        <td class="coluna-rotulo">{{ $coluna['rotulo'] }}</td>
                    @endforeach
                </tr>
            </table>

            <table class="legenda">
                <tr>
                    @foreach ($fatias as $fatia)
                        <td><span class="quadrado" style="background: {{ $fatia['cor'] }};"></span>{{ $fatia['rotulo'] }}</td>
                    @endforeach
                </tr>
            </table>
        </div>

        {{-- Operações e custo por hectare, lado a lado --}}
        <table class="lado-a-lado">
            <tr>
                <td class="metade">
                    <div class="secao">
                        <h2><span class="marcador"></span>Custo por tipo de operação</h2>
                        <div class="descricao">Operações que concentraram os custos.</div>

                        <table class="barras">
                            @foreach ($barrasOperacoes as $barra)
                                <tr>
                                    <td class="barra-rotulo">{{ $barra['rotulo'] }}</td>
                                    <td><div class="trilho"><div class="barra" style="width: {{ max($barra['largura'], 1) }}%;"></div></div></td>
                                    <td class="barra-valor num">{{ $barra['texto'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </td>
                <td class="metade">
                    <div class="secao">
                        <h2><span class="marcador"></span>Custo por hectare dos talhões</h2>
                        <div class="descricao">Custo total do talhão ÷ área do talhão.</div>

                        <table class="barras">
                            @foreach ($barrasCustoPorHectare as $barra)
                                <tr>
                                    <td class="barra-rotulo">{{ $barra['rotulo'] }}</td>
                                    <td><div class="trilho"><div class="barra" style="width: {{ max($barra['largura'], 1) }}%;"></div></div></td>
                                    <td class="barra-valor num">{{ $barra['texto'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Custos por talhão --}}
        <div class="secao">
            <h2><span class="marcador"></span>Custos por talhão</h2>
            <div class="descricao">Custos acumulados das atividades de cada talhão no período.</div>

            <table class="dados">
                <thead>
                    <tr>
                        <th>Talhão</th>
                        <th class="num">Área</th>
                        <th class="num">Ativ.</th>
                        <th class="num">Insumos</th>
                        <th class="num">Maquinário</th>
                        <th class="num">Mão de obra</th>
                        <th class="num">Custo total</th>
                        <th class="num">Custo/ha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($custosPorTalhao as $custo)
                        <tr>
                            <td>
                                <span class="forte">{{ $custo['talhao']->nome }}</span>
                                <span class="subtexto">{{ $custo['talhao']->cultura }} · safra {{ $custo['talhao']->safra }}</span>
                            </td>
                            <td class="num">{{ Number::format($custo['talhao']->area, 2) }} ha</td>
                            <td class="num">{{ $custo['quantidade_atividades'] }}</td>
                            <td class="num">{{ Number::currency($custo['custo_insumos']) }}</td>
                            <td class="num">{{ Number::currency($custo['custo_maquinas']) }}</td>
                            <td class="num">{{ Number::currency($custo['custo_mao_obra']) }}</td>
                            <td class="num forte">{{ Number::currency($custo['custo_total']) }}</td>
                            <td class="num forte">{{ $custo['custo_por_hectare'] !== null ? Number::currency($custo['custo_por_hectare']) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="num">{{ Number::format($resumo['area'], 2) }} ha</td>
                        <td class="num">{{ $resumo['quantidade_atividades'] }}</td>
                        <td class="num">{{ Number::currency($resumo['custo_insumos']) }}</td>
                        <td class="num">{{ Number::currency($resumo['custo_maquinas']) }}</td>
                        <td class="num">{{ Number::currency($resumo['custo_mao_obra']) }}</td>
                        <td class="num">{{ Number::currency($resumo['custo_total']) }}</td>
                        <td class="num">{{ $resumo['custo_por_hectare'] !== null ? Number::currency($resumo['custo_por_hectare']) : '—' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Atividades --}}
        <div class="secao-lista">
            <h2 style="margin: 0; font-size: 11pt; color: #18181b;"><span style="display: inline-block; width: 3pt; height: 10pt; margin-right: 6pt; background: #16a34a; border-radius: 1pt;"></span>Atividades que formaram o custo</h2>
            <div class="suave" style="margin: 2pt 0 9pt 9pt; font-size: 7.5pt;">
                Em ordem cronológica.
                @if ($resumo['quantidade_atividades'] > $limiteDeAtividades)
                    Exibindo as primeiras {{ $limiteDeAtividades }} de {{ $resumo['quantidade_atividades'] }} atividades.
                @endif
            </div>

            <table class="dados">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Talhão</th>
                        <th>Operação</th>
                        <th>Safra</th>
                        <th class="num">Insumos</th>
                        <th class="num">Maquinário</th>
                        <th class="num">Mão de obra</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($atividades as $atividade)
                        <tr>
                            <td style="white-space: nowrap;">{{ $atividade->data->format('d/m/Y') }}</td>
                            <td style="white-space: nowrap;">{{ $atividade->talhao->nome }}</td>
                            <td>
                                {{ $atividade->tipo->rotulo() }}
                                @if ($atividade->descricao)
                                    <span class="subtexto">{{ $atividade->descricao }}</span>
                                @endif
                            </td>
                            <td>{{ $atividade->safra }}</td>
                            <td class="num">{{ Number::currency($atividade->custo_insumos) }}</td>
                            <td class="num">{{ Number::currency($atividade->custo_maquinas) }}</td>
                            <td class="num">{{ Number::currency($atividade->custo_mao_obra) }}</td>
                            <td class="num forte">{{ Number::currency($atividade->custo_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Total ({{ $resumo['quantidade_atividades'] }} atividades)</td>
                        <td class="num">{{ Number::currency($resumo['custo_insumos']) }}</td>
                        <td class="num">{{ Number::currency($resumo['custo_maquinas']) }}</td>
                        <td class="num">{{ Number::currency($resumo['custo_mao_obra']) }}</td>
                        <td class="num">{{ Number::currency($resumo['custo_total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <div class="nota">
        <strong>Como os custos foram calculados.</strong>
        Custo do insumo = quantidade × custo unitário; custo da máquina = horas × custo por hora;
        custo da mão de obra = horas trabalhadas × custo por hora; custo total da atividade = insumos + maquinário + mão de obra.
        Custo por hectare = custo total ÷ área dos talhões. Os valores unitários e a safra são registrados no dia do lançamento de
        cada atividade: alterações posteriores nos cadastros não mudam os custos já apurados. Implementos não têm custo próprio
        (o custo deles está incluído no custo por hora das máquinas).
    </div>
</body>
</html>
