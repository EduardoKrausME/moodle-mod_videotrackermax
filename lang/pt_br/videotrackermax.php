<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * videotrackermax.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aggregationrebuilt'] = 'A agregação de analytics foi reconstruída.';
$string['allcohorts'] = 'Todas as coortes';
$string['allgroupings'] = 'Todos os agrupamentos';
$string['allparticipants'] = 'Todos os participantes';
$string['analyticssettings'] = 'Analytics';
$string['applyfilters'] = 'Aplicar filtros';
$string['authoritativeprogress'] = 'Progresso autoritativo do Video Bridge';
$string['averagesessions'] = 'Média de sessões';
$string['averagespeed'] = 'Velocidade média';
$string['averagewatched'] = 'Média assistida';
$string['averagewatchtime'] = 'Tempo médio';
$string['backtodashboard'] = 'Voltar para alunos';
$string['bucketcount'] = 'Granularidade do heatmap';
$string['bucketcount_help'] = 'Quantidade de buckets materializados na timeline. Isso não cria uma linha no banco a cada timeupdate do player.';
$string['compare'] = 'Comparar';
$string['comparefirst'] = 'Primeira população';
$string['comparesecond'] = 'Segunda população';
$string['comparetype'] = 'Comparar por';
$string['comparisonperiodhelp'] = 'Selecione datas inicial e final válidas para os dois períodos que deseja comparar.';
$string['comparisonunavailable'] = 'Não há populações disponíveis suficientes para esta comparação.';
$string['completedplural'] = 'Alunos concluíram';
$string['completiondetail:percent'] = 'Assistir pelo menos {$a}% do vídeo';
$string['completionpercent'] = 'Percentual mínimo assistido';
$string['completionstatus'] = 'Conclusão';
$string['correlationwarning'] = 'Estes analytics descrevem comportamento, não a causa. Um pico de replay pode indicar dificuldade ou importância, mas também pode ser apenas um comportamento natural de estudo.';
$string['dailybreakdown'] = 'Detalhamento por dia';
$string['dashboard'] = 'Analytics';
$string['dateperiods'] = 'Períodos de data';
$string['eventaggregationrebuilt'] = 'Agregação de analytics reconstruída';
$string['eventsatpoint'] = '{$a} eventos neste bucket';
$string['exportcsv'] = 'Exportar CSV';
$string['filterfrom'] = 'De';
$string['filterto'] = 'Até';
$string['fullname'] = 'Nome completo';
$string['heatmap:dropoffs'] = 'Abandono';
$string['heatmap:pauses'] = 'Pausas';
$string['heatmap:replays'] = 'Replay';
$string['heatmap:skips'] = 'Skips';
$string['heatmap:viewers'] = 'Visualização';
$string['heatmapmetric'] = 'Tipo de heatmap';
$string['individualheatmap'] = 'Mapa individual de visualização';
$string['individualnotavailable'] = 'Este aluno não está disponível no escopo atual do relatório.';
$string['individualreport'] = 'Relatório individual do aluno';
$string['maxdropoff'] = 'Maior abandono';
$string['maxpause'] = 'Maior concentração de pausas';
$string['maxpercent'] = 'Percentual máximo assistido';
$string['maxreplay'] = 'Trecho mais repetido';
$string['maxskip'] = 'Trecho mais pulado';
$string['medianwatched'] = 'Mediana assistida';
$string['metric'] = 'Métrica';
$string['metric:dropoffs'] = 'abandonos';
$string['metric:pauses'] = 'pausas';
$string['metric:replays'] = 'replays';
$string['metric:skips'] = 'skips';
$string['metric:viewers'] = 'alunos';
$string['minaggregateusers'] = 'Mínimo de usuários nos analytics coletivos';
$string['minaggregateusers_desc'] = 'Estatísticas e comparações coletivas são ocultadas quando a população selecionada possui menos usuários que este limite.';
$string['minpercent'] = 'Percentual mínimo assistido';
$string['minsessions'] = 'Mínimo de sessões';
$string['modulename'] = 'Video Tracker Max';
$string['modulenameplural'] = 'Atividades Video Tracker Max';
$string['nodata'] = 'Nenhum analytics consolidado corresponde aos filtros selecionados.';
$string['nosources'] = 'Nenhuma fonte do Video Bridge com tracking confiável está disponível.';
$string['notwatched'] = 'Não assistido';
$string['periodafrom'] = 'Período A de';
$string['periodato'] = 'Período A até';
$string['periodbfrom'] = 'Período B de';
$string['periodbto'] = 'Período B até';
$string['pluginname'] = 'Video Tracker Max';
$string['population'] = 'População';
$string['privacy:metadata'] = 'O Video Tracker Max materializa analytics compactos por aluno e dia derivados da telemetria do Video Bridge.';
$string['privacy:metadata:bucket'] = 'Analytics materializados por aluno e trecho da timeline.';
$string['privacy:metadata:bucket:bucket'] = 'O número normalizado do bucket na timeline.';
$string['privacy:metadata:bucket:userid'] = 'O aluno representado pelo bucket.';
$string['privacy:metadata:bucket:watched'] = 'Se o aluno assistiu ao bucket.';
$string['privacy:metadata:user'] = 'Resumo materializado por aluno e dia.';
$string['privacy:metadata:user:day'] = 'O dia do relatório.';
$string['privacy:metadata:user:percent'] = 'O percentual de buckets da timeline alcançados.';
$string['privacy:metadata:user:sessions'] = 'A quantidade de sessões de reprodução.';
$string['privacy:metadata:user:userid'] = 'O aluno representado pelo resumo.';
$string['privacy:metadata:user:watchtime'] = 'O tempo real estimado de reprodução.';
$string['privacy:path'] = 'Analytics do Video Tracker Max';
$string['reachedend'] = 'Chegaram ao final';
$string['rebuildaggregation'] = 'Reconstruir agregação';
$string['retention'] = 'Retenção';
$string['retentioncomparison'] = 'Comparação de retenção';
$string['retentioncurve'] = 'Curva de retenção';
$string['retentionhelp'] = 'A retenção mostra o percentual dos alunos da população filtrada que chegou a cada trecho do vídeo.';
$string['sessions'] = 'Sessões';
$string['showstudentprogress'] = 'Mostrar o próprio progresso ao aluno';
$string['source'] = 'Fonte do vídeo';
$string['started'] = 'Alunos iniciaram';
$string['suppressed'] = 'Os analytics coletivos foram ocultados porque esta seleção possui menos de {$a} usuários.';
$string['suppressedcomparison'] = 'A comparação foi ocultada porque pelo menos um dos lados possui menos de {$a} usuários.';
$string['suppressedexport'] = 'Esta exportação não está disponível porque a população selecionada está abaixo do limite de privacidade configurado.';
$string['tab:comparison'] = 'Comparação';
$string['tab:heatmap'] = 'Heatmap';
$string['tab:overview'] = 'Visão geral';
$string['tab:retention'] = 'Retenção';
$string['tab:students'] = 'Alunos';
$string['taskaggregate'] = 'Consolidar analytics do Video Tracker Max';
$string['value'] = 'Valor';
$string['videosource'] = 'Fonte do vídeo';
$string['videotrackermax:addinstance'] = 'Adicionar atividade Video Tracker Max';
$string['videotrackermax:export'] = 'Exportar analytics';
$string['videotrackermax:rebuild'] = 'Reconstruir agregação de analytics';
$string['videotrackermax:view'] = 'Visualizar Video Tracker Max';
$string['videotrackermax:viewanalytics'] = 'Visualizar analytics coletivos';
$string['videotrackermax:viewindividual'] = 'Visualizar analytics individuais';
$string['videotrackermaxname'] = 'Nome da atividade';
$string['viewingheatmap'] = 'Heatmap de visualização';
$string['watched'] = 'Assistido';
$string['watchedpercent'] = 'Percentual assistido';
$string['watchtime'] = 'Tempo assistido';
$string['yourprogress'] = 'Seu progresso';
