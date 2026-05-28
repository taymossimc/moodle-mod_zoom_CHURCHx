<?php
// This file is part of the Zoom plugin for Moodle - http://moodle.org/
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
 * Portuguese (Brazil) language strings for Zoom YT.
 *
 * @package    mod_zoomyt
 * @copyright  2026 TUCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accountid'] = 'ID da conta Zoom';
$string['accountid_desc'] = '';
$string['actions'] = 'Ações';
$string['activitydate:ended'] = 'Encerrada: ';
$string['activitydate:started'] = 'Iniciada: ';
$string['activitydate:starts'] = 'Inicia: ';
$string['addparticipant'] = 'Adicionar participante';
$string['addparticipantgroup'] = 'Adicionar grupo de participantes';
$string['addroom'] = 'Adicionar sala';
$string['addroomalert'] = 'Adicione uma sala clicando';
$string['addtocalendar'] = 'Adicionar ao calendário';
$string['allmeetings'] = 'Todas as reuniões';
$string['allmeetings_desc'] = 'Com esta configuração, você controla se um link para a página de índice da atividade Zoom será exibido no rodapé de cada página de visão geral da instância ou não. Isso afeta apenas a apresentação do link nessas páginas. Mesmo ocultando o link, o usuário pode acessar o índice por outros caminhos no curso.';
$string['allmeetings_disable'] = 'Desativar link de todas as reuniões';
$string['allmeetings_enable'] = 'Ativar link de todas as reuniões';
$string['alternative_hosts'] = 'Anfitriões alternativos';
$string['alternative_hosts_desc'] = 'Com esta configuração, você controla se a opção de escolher anfitriões alternativos é exibida nas configurações da instância. Há dois tipos: um campo de texto com e-mails separados por vírgula e um seletor de usuários com preenchimento automático para usuários matriculados no curso, com conta Zoom e papel entre {$a->roles}. Anfitriões definidos diretamente no Zoom mas não selecionáveis no Moodle continuam visíveis na visão geral e são preservados ao atualizar a reunião.';
$string['alternative_hosts_disable'] = 'Desativar opção de anfitriões alternativos';
$string['alternative_hosts_help'] = "A opção de anfitrião alternativo permite agendar reuniões e designar outros usuários Zoom para iniciá-las. Eles receberão um e-mail do Zoom com o link para iniciar.\n\nInforme o(s) e-mail(s) dos anfitriões alternativos, separados por vírgula (sem espaços).";
$string['alternative_hosts_inputfield'] = 'Exibir anfitriões alternativos como campo de texto simples';
$string['alternative_hosts_picker'] = 'Exibir anfitriões alternativos como seletor de usuários com preenchimento automático';
$string['alternative_hosts_picker_help'] = "A opção de anfitrião alternativo permite designar usuários Zoom matriculados neste curso para iniciar a reunião. Eles receberão um e-mail do Zoom com o link.\n\nVocê pode escolher um ou vários anfitriões alternativos.\n\nSe um usuário não aparecer no seletor, ele pode não estar matriculado com papel adequado ou não ter conta Zoom elegível.";
$string['alternative_hosts_picker_noneselected'] = 'Nenhum anfitrião alternativo selecionado';
$string['alternative_hosts_picker_placeholder'] = 'Selecione usuário(s)';
$string['autoaddinstructorsashosts'] = 'Adicionar automaticamente instrutores do curso como anfitriões alternativos';
$string['autoaddinstructorsashosts_desc'] = 'Quando ativado, todos os instrutores (com permissão para adicionar atividades Zoom) são adicionados automaticamente como anfitriões alternativos ao criar ou atualizar a reunião, permitindo que qualquer instrutor inicie e conduza a reunião.';
$string['autocreatezoomusers'] = 'Criar contas Zoom automaticamente para instrutores';
$string['autocreatezoomusers_desc'] = 'Quando ativado, instrutores sem conta Zoom são adicionados à sua conta e recebem licença Pro (com reciclagem de licença se necessário). Quando desativado, apenas quem já tem conta Zoom é adicionado como anfitrião alternativo.';
$string['fallback_host_email'] = 'E-mail do anfitrião reserva';
$string['fallback_host_email_desc'] = 'E-mail de um usuário Zoom licenciado na sua conta para ser anfitrião quando o e-mail do professor não estiver disponível (ex.: outra organização Zoom ou convite pendente). O professor ainda entra com controle total pelo URL de início e o nome exibido da conta reserva pode ser ajustado temporariamente.';
$string['fallback_host_not_configured'] = 'Seu e-mail não está disponível nesta conta Zoom e nenhum anfitrião reserva foi configurado. Entre em contato com o administrador.';
$string['fallback_host_not_found'] = 'O e-mail do anfitrião reserva configurado ({$a}) não foi encontrado na conta Zoom. Entre em contato com o administrador.';
$string['apiendpoint'] = 'Endpoint da API Zoom';
$string['apiendpoint_desc'] = 'Escolha qual endpoint da API Zoom a atividade usará. O endpoint global serve para a maioria. O da UE é para licenças com provisionamento na UE. Em dúvida, use o global.';
$string['apiendpoint_eu'] = 'Endpoint da API UE';
$string['apiendpoint_global'] = 'Endpoint da API global';
$string['apiidentifier'] = 'Identificador da API Zoom';
$string['apiidentifier_desc'] = 'Campo identificador usado nas chamadas à API Zoom';
$string['apiurl'] = 'URL da API Zoom';
$string['apiurl_desc'] = '';
$string['audio_both'] = 'Áudio do computador e telefone';
$string['audio_telephony'] = 'Somente telefone';
$string['audio_voip'] = 'Somente áudio do computador';
$string['audiodefault'] = 'Áudio padrão';
$string['authentication'] = 'Autenticação';
$string['autorecording_cloud'] = 'Nuvem';
$string['autorecording_local'] = 'Local';
$string['autorecording_none'] = 'Nenhuma';
$string['autorecording_userdefault'] = 'Usar configurações padrão do usuário Zoom';
$string['autorecordingoptionsupdate'] = 'Atualizar opções de gravação automática';
$string['breakoutrooms'] = 'Salas de grupo';
$string['cachedef_oauth'] = 'Cache de token OAuth Zoom';
$string['cachedef_zoomid'] = 'Mapeamentos de ID de usuário Zoom';
$string['cachedef_zoommeetingsecurity'] = 'Configurações de segurança de reuniões Zoom, incluindo requisitos de senha da conta';
$string['calendardescriptionintro'] = "\nDescrição:\n{\$a}";
$string['calendariconalt'] = 'Ícone de calendário';
$string['calendarjoinurl'] = 'URL de entrada na reunião: {$a}.';
$string['changehost'] = 'Alterar anfitrião';
$string['clickjoin'] = 'Clicou no botão de entrar na reunião';
$string['clientid'] = 'ID do cliente Zoom';
$string['clientid_desc'] = '';
$string['clientsecret'] = 'Segredo do cliente Zoom';
$string['clientsecret_desc'] = '';
$string['connectionfailed'] = 'Falha na conexão: ';
$string['connectionok'] = 'Conexão funcionando.';
$string['connectionsettings'] = 'Configurações de conexão';
$string['connectionsettings_desc'] = 'Estas configurações definem como o Moodle se conecta ao Zoom.';
$string['connectionstatus'] = 'Status da conexão';
$string['day'] = 'Dia(s)';
$string['defaultsettings'] = 'Configurações padrão do Zoom';
$string['defaultsettings_help'] = 'Definem os padrões para novas reuniões e webinars Zoom.';
$string['deletemeetingrecordings'] = 'Excluir gravações da reunião do Moodle';
$string['deleteroom'] = 'Excluir sala';
$string['displayfirstname'] = 'Somente nome';
$string['displayfullname'] = 'Nome completo';
$string['displayid'] = 'Somente (id do usuário)';
$string['displayidfullname'] = '(id do usuário) seguido do nome completo';
$string['displayleadtime'] = 'Exibir antecedência';
$string['displayleadtime_desc'] = 'Se ativado, a antecedência é mostrada aos usuários, informando quando podem entrar antes do horário agendado, reduzindo recarregamentos desnecessários.';
$string['displayleadtime_nohideif'] = 'Observação: só é aplicada se a configuração \'{$a}\' for maior que zero.';
$string['displaypassword'] = 'Exibir senha de acesso';
$string['displaypassword_help'] = 'Se ativado, a senha da reunião será sempre exibida para quem não é anfitrião.';
$string['downloadical'] = 'Baixar iCal';
$string['downloadical_desc'] = 'Controla se o link para baixar arquivo iCal aparece na página da atividade. Não afeta a entrada no calendário do Moodle quando há data de início.';
$string['downloadical_disable'] = 'Desativar link de download iCal';
$string['downloadical_enable'] = 'Ativar link de download iCal';
$string['duration'] = 'Duração';
$string['encryptiontype'] = 'Tipo de criptografia';
$string['encryptiontype_alwaysshow'] = 'Sempre exibir seletor de tipo de criptografia, mesmo sem E2E disponível';
$string['encryptiontype_desc'] = 'Controla se a opção de criptografia ponta a ponta versus reforçada aparece nas configurações da instância. Mesmo visível, o usuário precisa de E2E habilitado no Zoom para usá-la.';
$string['encryptiontype_disable'] = 'Desativar seletor de tipo de criptografia';
$string['encryptiontype_showonlyife2epossible'] = 'Exibir seletor apenas se o usuário puder usar criptografia ponta a ponta';
$string['end_date_option_after'] = 'Após';
$string['end_date_option_by'] = 'Até';
$string['end_date_option_occurrences'] = 'ocorrências';
$string['enddate'] = 'Data final';
$string['endtime'] = 'Hora de término';
$string['err_downloadicaldisabled'] = 'O download de arquivos iCal de reuniões Zoom está desativado.';
$string['err_downloadicalrecurringempty'] = 'Não é possível baixar iCal: a reunião não tem pelo menos uma ocorrência.';
$string['err_downloadicalrecurringnofixed'] = 'Não é possível baixar iCal: reunião recorrente sem horário fixo.';
$string['err_duration_nonpositive'] = 'A duração deve ser positiva.';
$string['err_duration_too_long'] = 'A duração não pode exceder 150 horas.';
$string['err_end_date'] = 'A data final da recorrência não pode estar no passado';
$string['err_end_date_before_start'] = 'A data final da recorrência não pode ser anterior à data de início';
$string['err_invalid_password'] = 'A senha contém caracteres inválidos.';
$string['err_long_timeframe'] = 'Intervalo solicitado muito longo; exibindo o último mês do intervalo.';
$string['err_password'] = 'A senha só pode conter: [a-z A-Z 0-9 @ - _ *]. Máximo 10 caracteres.';
$string['err_password_required'] = 'Senha obrigatória.';
$string['err_registration'] = 'O usuário atual não tem permissão para criar reunião/webinar com registro obrigatório.';
$string['err_repeat_monthly_interval'] = 'Intervalo máximo para reunião mensal é 3 meses';
$string['err_repeat_weekly_interval'] = 'Intervalo máximo para reunião semanal é 12 semanas';
$string['err_start_time_past'] = 'A data de início não pode estar no passado.';
$string['err_start_time_past_recurring'] = 'Para reuniões recorrentes, a data é a mais antiga possível para a próxima ocorrência e deve ser hoje ou no futuro.';
$string['err_weekly_days'] = 'Selecione o(s) dia(s) da reunião semanal recorrente';
$string['erroraddinstance'] = 'Não foi possível criar a reunião Zoom. Opções inválidas para reunião recorrente.';
$string['errorwebservice'] = 'Erro no webservice Zoom: {$a}.';
$string['errorwebservice_badrequest'] = 'O Zoom recebeu uma requisição inválida: {$a}';
$string['errorwebservice_notfound'] = 'O recurso não existe: {$a}';
$string['export'] = 'Exportar';
$string['externaluser'] = 'Usuário externo';
$string['firstjoin'] = 'Primeiro momento para entrar';
$string['firstjoin_desc'] = 'Quanto antes o usuário pode entrar em uma reunião agendada (minutos antes do início).';
$string['hostearlyaccess'] = 'Acesso antecipado do anfitrião/professor';
$string['hostearlyaccess_desc'] = 'Quantos minutos antes do início anfitriões e professores podem iniciar ou entrar.';
$string['joinbeforestart'] = 'Acesso antecipado para participantes';
$string['joinbeforestart_help'] = 'Quantos minutos antes do início os participantes podem entrar. Use "Usar padrão" para herdar da categoria ou do site.';
$string['usedefault'] = 'Usar padrão';
$string['noearlyaccess'] = 'Sem acesso antecipado';
$string['getmeetingrecordings'] = 'Obter gravações da reunião no Zoom';
$string['getmeetingreports'] = 'Obter relatório da reunião no Zoom';
$string['globalsettings'] = 'Configurações globais';
$string['globalsettings_desc'] = 'Estas configurações aplicam-se ao plugin Zoom como um todo.';
$string['grading_needgrade'] = "Os usuários abaixo precisam de nota manual por não terem sido identificados:\n";
$string['grading_notenrolled'] = "Os usuários abaixo entraram na reunião mas não foram reconhecidos como matriculados:\n";
$string['grading_notfound'] = "Usuários que clicaram para entrar mas não apareceram no relatório de participantes:\n";
$string['gradingentry'] = 'Ao entrar';
$string['gradinglink'] = 'Revisar ou atualizar notas';
$string['gradingmessagebody'] = 'Sessão da reunião Zoom: {$a->zoomurl};
<br>
Usuários avaliados automaticamente pela duração na reunião: {$a->graded}.
<br>
Usuários que já tinham nota: {$a->alreadygraded}.
<br>
{$a->needgrade}<br>
Revise ou atualize as notas aqui: {$a->gradeurl}
<br>
{$a->notfound}
<br>
{$a->notenrolled}';
$string['gradingmessagesubject'] = 'Notas dos usuários na reunião Zoom: {$a->name}';
$string['gradingmethod'] = 'Método de avaliação';
$string['gradingmethod_heading'] = 'Opções do método de avaliação';
$string['gradingmethod_heading_help'] = 'Escolha o método para avaliar a participação no Zoom.';
$string['gradingmethod_help'] = 'Escolha como avaliar a participação.<br>
Ao entrar: o aluno recebe nota máxima ao clicar para entrar no Moodle.<br>
Duração da presença: a nota é proporcional ao tempo na reunião em relação à duração total.<br>
Observações sobre duração da presença:<br>
- O nome exibido deve conter id ou nome completo.<br>
- Recomenda-se definir \'zoom | defaultjoinbeforehost\' como Não para duração precisa.<br>
- Alunos com cliente Zoom com dados diferentes do Moodle podem precisar de nota manual após o relatório.';
$string['gradingperiod'] = 'Duração da presença';
$string['gradingsmallmeassage'] = 'Relatório rápido de notas para {$a->name}:
<br>
Precisam de nota manual: {$a->number}
<br>
Usuários com nota: {$a->graded + $a->alreadygraded}';
$string['host'] = 'Anfitrião';
$string['hostintro'] = '<a target="_blank" href="https://support.zoom.us/hc/en-us/articles/208220166">Anfitriões alternativos</a> podem iniciar reuniões Zoom e gerenciar a sala de espera.';
$string['indicator:cognitivedepth'] = 'Cognitivo Zoom';
$string['indicator:cognitivedepth_help'] = 'Indicador baseado na profundidade cognitiva alcançada pelo aluno na atividade Zoom.';
$string['indicator:socialbreadth'] = 'Social Zoom';
$string['indicator:socialbreadth_help'] = 'Indicador baseado na amplitude social alcançada pelo aluno na atividade Zoom.';
$string['instanceusers'] = 'Verificar usuários da instância';
$string['instanceusers_desc'] = 'Se Redefinir licenças estiver ativo, verifica apenas usuários licenciados nesta instância Moodle. Útil quando várias instâncias compartilham um pool de licenças.';
$string['invalid_status'] = 'Status inválido; verifique o banco de dados.';
$string['invalidscheduleuser'] = 'Você não pode agendar para o usuário especificado.';
$string['invitation_dialin'] = 'Padrão de discagem';
$string['invitation_dialin_help'] = 'Expressão regular para localizar números de discagem da reunião Zoom.';
$string['invitation_h323'] = 'Padrão de mensagem H.323';
$string['invitation_h323_help'] = 'Expressão regular para informações H.323. Se os convites não tiverem SIP ou H.323, use string vazia. (Importante com depuração ativa, que pode quebrar exportações como iCal.)';
$string['invitation_icallink'] = 'Padrão de link iCal';
$string['invitation_icallink_help'] = 'Expressão regular para o link iCal da reunião Zoom.';
$string['invitation_invite'] = 'Padrão da mensagem de convite';
$string['invitation_invite_help'] = 'Expressão regular para a frase introdutória do convite.';
$string['invitation_joinurl'] = 'Padrão da URL de entrada';
$string['invitation_joinurl_help'] = 'Expressão regular para a URL de entrada da reunião.';
$string['invitation_onetapmobile'] = 'Padrão one tap mobile';
$string['invitation_onetapmobile_help'] = 'Expressão regular para os detalhes one tap mobile.';
$string['invitation_sip'] = 'Padrão SIP';
$string['invitation_sip_help'] = 'Expressão regular para informações SIP. Se não houver SIP/H.323 nos convites, use string vazia.';
$string['invitationmatchnotfound'] = 'Nenhuma correspondência no convite Zoom para o elemento "{$a->element}" com o padrão "{$a->pattern}".';
$string['invitationmodificationfailed'] = 'Erro na expressão regular do elemento "{$a->element}" com o padrão "{$a->pattern}".';
$string['invitationregex'] = 'Regex do convite Zoom e permissões';
$string['invitationregex_help'] = 'Defina padrões regex para isolar partes do convite e use permissões para controlar o que cada usuário vê.';
$string['invitationregex_nohideif'] = 'Observação: os padrões só são usados se a configuração \'{$a}\' estiver ativada.';
$string['invitationregexenabled'] = 'Ativar regex e permissões do convite Zoom';
$string['invitationregexenabled_help'] = 'Quando ativado, o convite é dividido em partes com as regex abaixo e as permissões decidem o que exibir. Veja zoom/viewjoinurl e zoom/viewdialin.';
$string['invitationremoveicallink'] = 'Remover link iCal do convite Zoom';
$string['invitationremoveicallink_help'] = 'Se ativado, o link iCal do e-mail é removido com o padrão invitation_icallink.';
$string['invitationremoveinvite'] = 'Remover mensagem de convite do e-mail Zoom';
$string['invitationremoveinvite_help'] = 'Se ativado, a frase introdutória é removida com o padrão invitation_invite.';
$string['join'] = 'Entrar';
$string['join_meeting'] = 'Entrar na reunião';
$string['joinbeforehost'] = 'Entrar antes do anfitrião';
$string['joinbeforehostenable'] = 'Sim';
$string['anytime'] = 'A qualquer momento (igual aos professores)';
$string['joinlink'] = 'Link de entrada';
$string['jointime'] = 'Hora de entrada';
$string['leavetime'] = 'Hora de saída';
$string['licenseonjoin'] = 'Marque se deseja que o anfitrião receba licença ao <i>iniciar</i> a reunião <i>e</i> ao criá-la.';
$string['licensesettings'] = 'Configurações de licença';
$string['licensesettings_desc'] = 'Definem como o Moodle gerencia suas licenças Zoom.';
$string['licensesnumber'] = 'Número de licenças';
$string['lowlicenses'] = 'Se o número de licenças for insuficiente, ao criar cada nova atividade o usuário receberá licença PRO rebaixando outro. Efetivo quando há mais de 5 licenças PRO ativas.';
$string['maskparticipantdata'] = 'Ocultar dados de participantes';
$string['maskparticipantdata_help'] = 'Impede que dados de participantes apareçam em relatórios (útil para conformidade, ex.: HIPAA).';
$string['media'] = 'Mídia';
$string['meeting_finished'] = 'Encerrada';
$string['meeting_invite'] = 'Telefone / discagem';
$string['meeting_invite_hide'] = 'Ocultar convite da reunião';
$string['meeting_invite_show'] = 'Mostrar convite da reunião';
$string['meeting_nonexistent_on_zoom'] = 'Inexistente no Zoom';
$string['meeting_not_started'] = 'Não iniciada';
$string['meeting_started'] = 'Em andamento';
$string['meeting_time'] = 'Horário de início';
$string['meetingactivityurl'] = 'URL da atividade da reunião: <a href="{$a}">{$a}</a>';
$string['meetingcapacitywarning'] = 'Aviso de capacidade da reunião';
$string['meetingcapacitywarning_desc'] = 'Exibe aviso se houver mais participantes matriculados e ativos no curso do que a capacidade da licença Zoom do anfitrião. Visível ao anfitrião e anfitriões alternativos na página da atividade. A mensagem pode ser personalizada no idioma do Moodle.';
$string['meetingcapacitywarning_disable'] = 'Desativar aviso de capacidade';
$string['meetingcapacitywarning_enable'] = 'Ativar aviso de capacidade';
$string['meetingcapacitywarningbodyalthost'] = 'A licença Zoom do anfitrião desta reunião, {$a->hostname}, permite até <strong>{$a->meetingcapacity} participantes</strong>, mas o curso tem <strong><a href="{$a->courseparticipantsurl}">{$a->eligiblemeetingparticipants} participantes matriculados e ativos</a></strong>.';
$string['meetingcapacitywarningbodyrealhost'] = 'Sua licença Zoom permite até <strong><a href="{$a->zoomprofileurl}" target="_blank">{$a->meetingcapacity} participantes</a></strong>, mas o curso tem <strong><a href="{$a->courseparticipantsurl}">{$a->eligiblemeetingparticipants} participantes matriculados e ativos</a></strong>.';
$string['meetingcapacitywarningcontactalthost'] = 'Peça ao anfitrião que solicite ao proprietário da conta Zoom uma licença maior se todos precisarem entrar.';
$string['meetingcapacitywarningcontactrealhost'] = 'Solicite ao proprietário da conta Zoom uma licença maior se todos os participantes do curso precisarem entrar.';
$string['meetingcapacitywarningheading'] = 'Aviso de capacidade da reunião:';
$string['meetingparticipantsdeleted'] = 'Dados de participantes da reunião excluídos.';
$string['meetingrecordingviewsdeleted'] = 'Dados de visualização de gravações excluídos.';
$string['messageprovider:ical_notifications'] = 'Enviar convites iCal para novo evento Zoom aos participantes.';
$string['messageprovider:teacher_notification'] = 'Notificar professores sobre notas por duração na sessão Zoom';
$string['modulename'] = 'Reunião Zoom YT';
$string['modulename_help'] = 'O Zoom YT é uma plataforma de videoconferência que permite a usuários autorizados realizar reuniões online.';
$string['modulenameplural'] = 'Reuniões Zoom YT';
$string['month'] = 'Mês(es)';
$string['month_day_text'] = 'do mês';
$string['newmeetings'] = 'Novas reuniões';
$string['nextoccurrence'] = 'Próxima ocorrência';
$string['nomeetinginstances'] = 'Nenhuma sessão encontrada para esta reunião.';
$string['nonrecognizedusergrade'] = '(Nome: {$a->userid}, nota: {$a->grade})';
$string['nooccurrenceleft'] = 'A última ocorrência já terminou';
$string['noparticipants'] = 'Nenhum participante encontrado nesta sessão no momento.';
$string['norecordings'] = 'Nenhuma gravação encontrada para esta reunião no momento.';
$string['norooms'] = 'Nenhuma sala';
$string['nosessions'] = 'Nenhuma sessão encontrada no intervalo especificado.';
$string['nozooms'] = 'Nenhuma reunião';
$string['nozoomsfound'] = 'Nenhuma reunião encontrada para este curso.';
$string['occurson'] = 'Ocorre em';
$string['off'] = 'Desligado';
$string['oldmeetings'] = 'Reuniões concluídas';
$string['on'] = 'Ligado';
$string['option_allow_recording_change'] = 'Permitir alterar gravação';
$string['option_allow_recording_change_help'] = 'Permite ao usuário alterar a configuração de gravação ao criar a atividade';
$string['option_audio'] = 'Opções de áudio';
$string['option_audio_help'] = 'Permite discar apenas por telefone, apenas áudio do computador ou ambos';
$string['option_authenticated_users'] = 'Exigir autenticação para entrar';
$string['option_authenticated_users_help'] = "Exige que todos entrem com conta Zoom autorizada. <em>Não</em> se refere ao login no Moodle.";
$string['option_auto_recording'] = 'Gravação automática';
$string['option_auto_recording_help'] = 'Quando ativado, a reunião será gravada automaticamente';
$string['option_encryption_type'] = 'Criptografia';
$string['option_encryption_type_endtoendencryption'] = 'Criptografia ponta a ponta';
$string['option_encryption_type_enhancedencryption'] = 'Criptografia reforçada';
$string['option_encryption_type_help'] = "Controle o nível de criptografia e privacidade.\n\n*Criptografia reforçada*: a chave fica na nuvem Zoom.\n\n*Ponta a ponta*: a chave fica no seu dispositivo.\n\nCom E2E, alguns recursos ficam indisponíveis — [veja a documentação Zoom](https://support.zoom.us/hc/en-us/articles/360048660871).";
$string['option_host_video'] = 'Vídeo do anfitrião';
$string['option_host_video_help'] = 'Ativa o vídeo do anfitrião ao entrar. Mesmo desativado, o anfitrião pode ligar a câmera.';
$string['option_jbh'] = 'Alunos podem entrar antes do anfitrião';
$string['option_jbh_help'] = "Permite que alunos entrem antes do professor/anfitrião ou quando o anfitrião não comparece. A reunião inicia com o primeiro participante.\n\nExclusivo com a sala de espera: um desativa o outro.";
$string['option_mute_upon_entry'] = 'Silenciar participantes ao entrar';
$string['option_mute_upon_entry_help'] = 'Silencia todos ao entrarem; podem ativar o microfone depois.';
$string['option_participants_video'] = 'Vídeo dos participantes';
$string['option_participants_video_help'] = 'Ativa o vídeo dos participantes ao entrarem. Mesmo desativado, podem ligar a câmera.';
$string['option_proxyhost'] = 'Usar proxy';
$string['option_proxyhost_desc'] = 'Proxy no formato \'<code>&lt;hostname&gt;:&lt;port&gt;</code>\' só para comunicação com Zoom. Vazio = proxy padrão do Moodle.';
$string['option_view_recordings'] = 'Permitir visualizar gravações';
$string['option_waiting_room'] = 'Sala de espera';
$string['option_waiting_room_help'] = "Permite ao anfitrião controlar quando cada participante entra.\n\nExclusivo com 'Entrar antes do anfitrião'.";
$string['participantdatanotavailable'] = 'Detalhes indisponíveis';
$string['participantdatanotavailable_help'] = 'Dados de participantes indisponíveis nesta sessão (ex.: conformidade HIPAA).';
$string['participantgroups'] = 'Grupos de participantes';
$string['participants'] = 'Participantes';
$string['password'] = 'Senha de acesso';
$string['password_allowed_char'] = 'A senha só pode conter: [a-z A-Z 0-9 @ - _ *].';
$string['password_consecutive'] = 'Máximo de {$a} caracteres consecutivos (abcd, 1111, 1234, etc.).';
$string['password_length'] = 'Mínimo de {$a} caractere(s).';
$string['password_letter'] = 'A senha deve conter pelo menos 1 letra.';
$string['password_lower_upper'] = 'A senha deve ter letras minúsculas e maiúsculas.';
$string['password_max_length'] = 'Máximo de 10 caracteres.';
$string['password_number'] = 'A senha deve conter pelo menos 1 número.';
$string['password_only_numeric'] = 'A senha pode conter apenas números.';
$string['password_special'] = 'A senha deve ter pelo menos 1 caractere especial (@-_*).';
$string['passwordprotected'] = 'Protegida por senha';
$string['pluginadministration'] = 'Gerenciar reunião Zoom';
$string['pluginname'] = 'Reunião Zoom YT';
$string['privacy:metadata:zoom_breakout_participants'] = 'Tabela com participantes das salas de grupo';
$string['privacy:metadata:zoom_breakout_participants:userid'] = 'ID do usuário participante';
$string['privacy:metadata:zoom_meeting_details'] = 'Informações sobre cada instância de reunião';
$string['privacy:metadata:zoom_meeting_details:topic'] = 'Nome da reunião que o usuário frequentou';
$string['privacy:metadata:zoom_meeting_participants'] = 'Informações sobre participantes da reunião';
$string['privacy:metadata:zoom_meeting_participants:duration'] = 'Tempo que o participante permaneceu na reunião';
$string['privacy:metadata:zoom_meeting_participants:join_time'] = 'Hora em que o participante entrou';
$string['privacy:metadata:zoom_meeting_participants:leave_time'] = 'Hora em que o participante saiu';
$string['privacy:metadata:zoom_meeting_participants:name'] = 'Nome do participante';
$string['privacy:metadata:zoom_meeting_participants:user_email'] = 'E-mail do participante';
$string['privacy:metadata:zoom_meeting_view'] = 'Rastreia usuários que visualizaram gravações';
$string['privacy:metadata:zoom_meeting_view:userid'] = 'ID do usuário que visualizou a gravação';
$string['protectedgroups'] = 'Proteger grupos';
$string['protectedgroups_desc'] = 'Selecione grupos Zoom cujos membros não terão licença redefinida';
$string['recording'] = 'Gravação';
$string['recordingadd'] = 'Adicionar gravação';
$string['recordingdate'] = 'Data da gravação';
$string['recordingdelete'] = 'Tem certeza de que deseja excluir a gravação "{$a}"?';
$string['recordinghide'] = 'Ocultar gravação (visível no momento)';
$string['recordinglink'] = 'Link da gravação';
$string['recordingname'] = 'Título';
$string['recordingnotfound'] = 'Gravação não encontrada';
$string['recordingnotvisible'] = 'Gravação não visível. Contate o administrador se achar que é um erro.';
$string['recordingpasscode'] = 'Senha da gravação';
$string['recordings'] = 'Gravações';
$string['recordingshow'] = 'Mostrar gravação (oculta no momento)';
$string['recordingshowtoggle'] = 'Alternar visibilidade da gravação';
$string['recordingtype_active_speaker'] = 'Palestrante ativo';
$string['recordingtype_audio_interpretation'] = 'Interpretação de áudio';
$string['recordingtype_audio_only'] = 'Somente áudio';
$string['recordingtype_audio_transcript'] = 'Transcrição de áudio';
$string['recordingtype_chat'] = 'Arquivo de chat';
$string['recordingtype_closed_caption'] = 'Legendas ocultas';
$string['recordingtype_gallery'] = 'Vista em galeria';
$string['recordingtype_poll'] = 'Enquete';
$string['recordingtype_production_studio'] = 'Estúdio de produção';
$string['recordingtype_shared'] = 'Tela compartilhada';
$string['recordingtype_shared_gallery'] = 'Tela compartilhada com galeria';
$string['recordingtype_shared_speaker'] = 'Tela compartilhada com palestrante';
$string['recordingtype_shared_speaker_cc'] = 'Tela compartilhada com palestrante (CC)';
$string['recordingtype_sign'] = 'Interpretação em sinais';
$string['recordingtype_speaker'] = 'Vista do palestrante';
$string['recordingtype_summary'] = 'Resumo';
$string['recordingtype_summary_next_steps'] = 'Próximos passos do resumo';
$string['recordingtype_summary_smart_chapters'] = 'Capítulos inteligentes do resumo';
$string['recordingtype_timeline'] = 'Linha do tempo';
$string['recordingurl'] = 'URL da gravação';
$string['recordingview'] = 'Ver gravações';
$string['recordingvisibility'] = 'As gravações desta reunião ficam visíveis por padrão?';
$string['recordingvisibility_help'] = 'Ao buscar novas gravações, devem aparecer no Moodle por padrão?';
$string['recreatesuccessful'] = 'Reunião recriada com sucesso';
$string['recurrence_option_daily'] = 'Diária';
$string['recurrence_option_monthly'] = 'Mensal';
$string['recurrence_option_no_time'] = 'Sem horário fixo';
$string['recurrence_option_weekly'] = 'Semanal';
$string['recurrencetype'] = 'Recorrência';
$string['recurringmeeting'] = 'Reunião recorrente';
$string['recurringmeeting_help'] = 'Torna a reunião recorrente sem data ou hora de término; pode ser acessada a qualquer momento.';
$string['recurringmeetingexplanation'] = 'A reunião não tem data nem hora de término';
$string['recurringmeetinglong'] = 'Reunião recorrente (sem data ou hora de término)';
$string['recurringmeetingthisis'] = 'Esta é uma reunião recorrente';
$string['recycleonjoin'] = 'Reciclar licença ao entrar';
$string['redefinelicenses'] = 'Redefinir licenças';
$string['refreshreports'] = 'Atualizar relatórios de sessão';
$string['register'] = 'Registrar';
$string['registration'] = 'Exigir registro';
$string['registration_help'] = 'Obriga os participantes a se registrarem no Zoom antes de entrar.';
$string['registration_text'] = 'Obrigar registro na reunião/webinar';
$string['repeatinterval'] = 'Repetir a cada';
$string['report'] = 'Relatórios';
$string['reportapicalls'] = 'Relatar esgotamento de chamadas à API';
$string['requirepasscode'] = 'Exigir senha da reunião';
$string['requirepasscode_help'] = 'O anfitrião deve definir senha; participantes externos precisam digitá-la. Quem entra pelo Moodle pode não precisar.';
$string['resetapicalls'] = 'Redefinir número de chamadas à API disponíveis';
$string['resetzoomsall'] = 'Excluir todas as notas de usuários, dados de visualização de gravações e dados de participantes.';
$string['room'] = 'Sala';
$string['roomname'] = 'Nome da sala';
$string['rooms'] = 'Salas';
$string['schedule'] = 'Agenda';
$string['schedulefor'] = 'Agendar reunião para';
$string['schedulefor_help'] = 'Você pode agendar em nome de outro usuário que tenha concedido privilégio de agendamento no Zoom. O selecionado será o anfitrião e usará sua licença.';
$string['scheduleforself'] = 'Você mesmo';
$string['schedulingprivilege'] = 'Privilégio de agendamento';
$string['schedulingprivilege_desc'] = 'Controla se a opção aparece nas configurações da instância. O usuário ainda precisa do privilégio concedido no Zoom.';
$string['schedulingprivilege_disable'] = 'Desativar opção de privilégio de agendamento';
$string['schedulingprivilege_enable'] = 'Ativar opção de privilégio de agendamento';
$string['search:activity'] = 'Zoom — informações da atividade';
$string['security'] = 'Segurança';
$string['selectionarea'] = 'Nenhuma seleção';
$string['sendicalnotifications'] = 'Enviar notificações iCal';
$string['sendicalnotifications_help'] = "Permite enviar notificações iCal pela tarefa agendada 'Enviar notificação iCal'.";
$string['sendicalnotifications_warning'] = "Anexos devem estar habilitados em Administração do site / Servidor / E-mail / Configuração de e-mail de saída.";
$string['sessions'] = 'Sessões';
$string['sessionsreport'] = 'Relatório de sessões';
$string['sesskeyinvalid'] = 'Sessão inválida. Não é possível continuar.';
$string['setpasscode'] = 'Definir senha';
$string['showmedia'] = 'Exibir seção Mídia';
$string['showmedia_help'] = 'Mostra a seção Mídia na página da reunião.';
$string['showmediaonview'] = 'Mostrar Mídia na página da reunião';
$string['showschedule'] = 'Exibir seção Agenda';
$string['showschedule_help'] = 'Mostra a seção Agenda na página da reunião.';
$string['showscheduleonview'] = 'Mostrar Agenda na página da reunião';
$string['showjoinbutton'] = 'Mostrar botão Entrar na página do curso';
$string['showjoinbutton_help'] = 'Se ativado, um botão "Entrar agora" aparece na descrição da atividade na página principal do curso. Se a reunião ainda não estiver disponível, aparece "Reunião ainda não disponível".';
$string['showjoinbuttondesc'] = 'Exibir botão Entrar na listagem do curso';
$string['joinnow'] = 'Entrar agora';
$string['meetingnotyetavailable'] = 'Reunião ainda não disponível';
$string['viewrecordings'] = 'Ver gravações';
$string['sessionended'] = 'Sessão encerrada';
$string['showsecurity'] = 'Exibir seção Segurança';
$string['showsecurity_help'] = 'Mostra a seção Segurança na página da reunião.';
$string['showsecurityonview'] = 'Mostrar Segurança na página da reunião';
$string['start'] = 'Iniciar';
$string['start_meeting'] = 'Iniciar reunião';
$string['start_time'] = 'Quando';
$string['starthostjoins'] = 'Iniciar vídeo quando o anfitrião entrar';
$string['startpartjoins'] = 'Iniciar vídeo quando o participante entrar';
$string['starttime'] = 'Hora de início';
$string['status'] = 'Status';
$string['supplementaryfeaturessettings'] = 'Recursos suplementares';
$string['supplementaryfeaturessettings_desc'] = 'Controla se e como recursos suplementares do Zoom são oferecidos.';
$string['title'] = 'Título';
$string['topic'] = 'Assunto';
$string['trackingfields'] = 'Campos de rastreamento';
$string['trackingfields_help'] = 'Informe nomes/rótulos dos campos, separados por vírgula, para ativar nas atividades Zoom.';
$string['trackingfields_recommendedvalues'] = 'Valores recomendados: ';
$string['unamedisplay'] = 'Nome exibido do usuário';
$string['unamedisplay_help'] = 'Como exibir o nome em reuniões (apenas para quem não está logado no cliente Zoom).';
$string['unavailable'] = 'Você não pode entrar neste momento.';
$string['unavailablefinished'] = 'A reunião já terminou.';
$string['unavailablefirstjoin'] = 'Você pode entrar no máximo {$a->mins} minutos antes do horário agendado.';
$string['unavailablenotstartedyet'] = 'A reunião ainda não começou.';
$string['unavailableteacherearly'] = 'Como instrutor, você pode iniciar a reunião {$a->mins} minutos antes do horário agendado.';
$string['updatemeetings'] = 'Atualizar configurações da reunião a partir do Zoom';
$string['updatetrackingfields'] = 'Atualizar campos de rastreamento a partir do Zoom';
$string['usepersonalmeeting'] = 'Usar ID de reunião pessoal {$a}';
$string['waitingroom'] = 'Sala de espera';
$string['waitingroomenable'] = 'Ativar sala de espera';
$string['webinar'] = 'Webinar';
$string['webinar_already_false'] = '<p><b>Este módulo já está definido como reunião, não webinar. Não é possível alterar após a criação.</b></p>';
$string['webinar_already_true'] = '<p><b>Este módulo já está definido como webinar, não reunião. Não é possível alterar após a criação.</b></p>';
$string['webinar_alwaysshow'] = 'Sempre exibir opção de webinar, mesmo sem licença';
$string['webinar_by_default'] = 'Webinar por padrão';
$string['webinar_by_default_desc'] = 'Criar instância Zoom como webinar por padrão.';
$string['webinar_desc'] = 'Controla se a opção de webinar aparece ao criar reunião. O usuário ainda precisa de licença válida para webinar.';
$string['webinar_disable'] = 'Desativar webinars';
$string['webinar_help'] = "Webinars oferecem mais controle para públicos maiores.\n\nDisponível apenas para contas Zoom pré-autorizadas.";
$string['webinar_showonlyiflicense'] = 'Mostrar webinar apenas se o usuário tiver licença';
$string['webinarthisis'] = 'Isto é um webinar';
$string['week'] = 'Semana(s)';
$string['weekoption_first'] = 'Primeira';
$string['weekoption_fourth'] = 'Quarta';
$string['weekoption_last'] = 'Última';
$string['weekoption_second'] = 'Segunda';
$string['weekoption_third'] = 'Terceira';
$string['zoom:addinstance'] = 'Adicionar nova reunião Zoom';
$string['zoom:eligiblealternativehost'] = 'Pode ser selecionado como anfitrião alternativo';
$string['zoom:refreshsessions'] = 'Atualizar relatórios de reuniões Zoom';
$string['zoom:view'] = 'Ver reuniões Zoom';
$string['zoom:viewdialin'] = 'Ver informações de discagem Zoom';
$string['zoom:viewjoinurl'] = 'Ver URL de entrada Zoom';
$string['zoomerr'] = 'Ocorreu um erro com o Zoom.';
$string['zoomerr_alternativehostusernotfound'] = 'Usuário {$a} não encontrado no Zoom.';
$string['zoomerr_apilimit'] = 'Limite diário da API atingido. Tente novamente em {$a}';
$string['zoomerr_field_missing'] = '{$a} não encontrado';
$string['zoomerr_id_missing'] = 'Especifique o ID do course_module ou o ID da instância';
$string['zoomerr_licensesnumber_missing'] = 'Configuração Zoom utmost encontrada, mas licensesnumber não';
$string['zoomerr_maxretries'] = 'Tentou {$a->maxretries} vezes; falhou: {$a->response}';
$string['zoomerr_meetingnotfound'] = 'Reunião não encontrada no Zoom. Você pode <a href="{$a->recreate}">recriá-la aqui</a> ou <a href="{$a->delete}">excluí-la completamente</a>.';
$string['zoomerr_meetingnotfound_info'] = 'Reunião não encontrada no Zoom. Contate o anfitrião em caso de dúvidas.';
$string['zoomerr_no_access_token'] = 'Nenhum token de acesso retornado';
$string['zoomerr_scopes'] = 'A configuração OAuth do Zoom não inclui estes escopos obrigatórios: {$a}';
$string['zoomerr_usernotfound'] = 'Não foi possível encontrar sua conta no Zoom. Na primeira vez, ative a conta em <a href="{$a}" target="_blank">{$a}</a>, recarregue a página e continue. Caso contrário, confira se o e-mail no Zoom coincide com o deste sistema.';
$string['zoomerr_viewrecordings_off'] = 'Ver gravações está desativado; a tarefa não pode ser executada';
$string['zoomurl'] = 'URL da página inicial do Zoom';
$string['zoomurl_desc'] = '';

// Category-level settings strings.
$string['categorysettings'] = 'Configurações de categoria Zoom YT';
$string['categorysettings_inherit'] = 'Herança de configurações';
$string['categorysettings_connection'] = 'Conexão da conta Zoom';
$string['categorysettings_defaults'] = 'Padrões da reunião';
$string['categorysettings_saved'] = 'Configurações da categoria salvas com sucesso.';
$string['categorysettings_deleted'] = 'Configurações da categoria excluídas. Esta categoria herdará da categoria pai.';
$string['inherit_from_parent'] = 'Herdar da categoria pai';
$string['inherit_from_parent_desc'] = 'Usar configurações da categoria pai ou globais.';
$string['inherit_from_parent_help'] = 'Quando ativado, esta categoria usa as configurações de conta Zoom da categoria pai. Se não houver, usa as globais. Desative para configurar uma conta Zoom específica.';

// Granular inheritance strings.
$string['inherit_zoom_settings'] = 'Herdar configurações da conta Zoom';
$string['inherit_zoom_settings_desc'] = 'Usar credenciais da API da categoria pai ou globais.';
$string['inherit_zoom_settings_help'] = 'Quando ativado, usa Account ID, Client ID e Client Secret da categoria pai ou globais. Desative para credenciais próprias.';
$string['inherit_meeting_defaults'] = 'Herdar padrões da reunião';
$string['inherit_meeting_defaults_desc'] = 'Usar opções padrão da categoria pai ou globais.';
$string['inherit_meeting_defaults_help'] = 'Quando ativado, herda recorrência, sala de espera, vídeo etc. da categoria pai ou globais.';
$string['inherit_youtube_settings'] = 'Herdar configurações do YouTube';
$string['inherit_youtube_settings_desc'] = 'Usar canal do YouTube da categoria pai ou globais.';
$string['inherit_youtube_settings_help'] = 'Quando ativado, herda a conexão do canal YouTube. Desative para um canal específico nesta categoria.';

// YouTube category strings.
$string['youtube_site_credentials_required'] = 'As credenciais da API YouTube devem estar configuradas nas configurações globais do plugin antes de conectar um canal.';
$string['youtube_site_connection'] = 'Conexão do canal YouTube em todo o site';
$string['youtube_site_channel_connected'] = 'Conectado ao canal YouTube: {$a}';
$string['youtube_site_channel_not_connected'] = 'Nenhum canal YouTube conectado em todo o site';
$string['youtube_site_channel_desc'] = 'Este canal será o padrão para todas as atividades Zoom YT, salvo substituição por categoria ou atividade.';
$string['youtube_site_connect_desc'] = 'Conecte um canal YouTube como padrão para envio de gravações Zoom em todo o site.';
$string['youtube_site_default_channel'] = 'Canal YouTube padrão';
$string['youtube_site_default_channel_desc'] = 'Canal padrão para gravações em todo o site.';
$string['youtube_disconnect_confirm'] = 'Desconectar este canal YouTube?';
$string['youtube_disconnected'] = 'Canal YouTube desconectado.';
$string['yt_change_channel'] = 'Alterar canal';
$string['back_to_settings'] = 'Voltar às configurações Zoom YT';
$string['youtube_category_channel_connected'] = 'Conectado a: {$a}';
$string['youtube_category_channel_not_connected'] = 'Nenhum canal conectado para esta categoria.';
$string['youtube_manage_connection'] = 'Gerenciar conexão';

$string['using_global_settings'] = 'Usando configurações globais do Zoom.';
$string['using_own_settings'] = 'Esta categoria tem conta Zoom própria.';
$string['inheriting_from_category'] = 'Herdando da categoria: {$a}';
$string['testconnection'] = 'Testar conexão';
$string['deletecategorysettings'] = 'Excluir configurações da categoria';
$string['deletecategorysettings_confirm'] = 'Excluir as configurações desta categoria? Ela herdará da categoria pai.';
$string['connectionok'] = 'Conexão bem-sucedida! As credenciais Zoom são válidas.';
$string['connectionfailed'] = 'Falha na conexão.';
$string['error_missing_credentials'] = 'Informe todas as credenciais obrigatórias (Account ID, Client ID e Client Secret).';
$string['apiendpoint'] = 'Endpoint da API Zoom';
$string['apiendpoint_global'] = 'Global (api.zoom.us)';
$string['apiendpoint_eu'] = 'UE (eu01api-www4local.zoom.us)';
$string['zoomyt:managecategorysettings'] = 'Gerenciar configurações Zoom YT da categoria';
$string['zoomyt:viewcategorysettings'] = 'Ver configurações Zoom YT da categoria';
$string['categorysettings_link'] = 'Configurações Zoom YT';
$string['categoryzooomyt_settings'] = 'Gerenciar conta Zoom YT desta categoria';
$string['no_category_settings_access'] = 'Você não tem permissão para gerenciar configurações de categoria.';
$string['category_settings_header'] = 'Contas Zoom por categoria';
$string['category_settings_header_desc'] = 'Configure contas Zoom diferentes por categoria de curso (departamentos, escolas, etc.).';
$string['manage_category_settings'] = 'Gerenciar Zoom da categoria';
$string['category_settings_list'] = 'Configurações Zoom das categorias';
$string['category_settings_list_desc'] = 'Configure contas por categoria. Categorias sem configuração própria herdam da pai ou usam as globais.';
$string['configure'] = 'Configurar';
$string['no_categories_available'] = 'Nenhuma categoria disponível ou sem permissão para gerenciar.';
$string['back_to_settings'] = 'Voltar às configurações Zoom YT';

// YouTube Integration strings.
$string['youtube_settings'] = 'Integração YouTube';
$string['youtube_settings_desc'] = 'Configure integração para envio automático de gravações Zoom ao YouTube.';
$string['youtube_client_id'] = 'Client ID do YouTube';
$string['youtube_client_id_desc'] = 'OAuth 2.0 Client ID do projeto no Google Cloud. Padrão em todo o site.';
$string['youtube_client_id_help'] = 'OAuth 2.0 Client ID do projeto no Google Cloud.';
$string['youtube_client_secret'] = 'Client Secret do YouTube';
$string['youtube_client_secret_desc'] = 'OAuth 2.0 Client Secret do projeto no Google Cloud. Padrão em todo o site.';
$string['youtube_client_secret_help'] = 'OAuth 2.0 Client Secret do projeto no Google Cloud.';
$string['youtube_channel'] = 'Canal YouTube conectado';
$string['youtube_connect'] = 'Conectar ao YouTube';
$string['youtube_disconnect'] = 'Desconectar YouTube';
$string['youtube_connected_success'] = 'Conectado ao canal YouTube: {$a}';
$string['youtube_connection_ok'] = 'YouTube conectado: {$a}';
$string['youtube_connection_failed'] = 'Falha na conexão com o YouTube.';
$string['youtube_not_configured'] = 'YouTube não configurado para esta categoria.';
$string['youtube_credentials_required'] = 'Informe Client ID e Client Secret do YouTube antes de conectar.';
$string['youtube_oauth_error'] = 'Erro OAuth YouTube: {$a}';
$string['youtube_oauth_state_mismatch'] = 'Falha na validação de segurança. Tente novamente.';
$string['youtube_api_error'] = 'Erro da API YouTube: {$a}';
$string['youtube_no_channel'] = 'Nenhum canal YouTube encontrado para esta conta.';
$string['youtube_video_not_found'] = 'Vídeo YouTube não encontrado: {$a}';
$string['youtube_file_not_found'] = 'Arquivo de vídeo não encontrado: {$a}';
$string['youtube_upload_error'] = 'Erro no envio ao YouTube: {$a}';
$string['youtube_upload_init_error'] = 'Falha ao iniciar envio ao YouTube: {$a}';
$string['youtube_upload_no_location'] = 'O YouTube não retornou local de upload.';
$string['youtube_upload_chunk_error'] = 'Falha ao enviar parte do vídeo: {$a}';
$string['youtube_file_open_error'] = 'Não foi possível abrir o arquivo para envio: {$a}';
$string['youtube_default_visibility'] = 'Visibilidade padrão no YouTube';
$string['youtube_default_visibility_desc'] = 'Visibilidade padrão dos vídeos enviados.';
$string['youtube_default_visibility_help'] = 'Visibilidade padrão; pode ser alterada no nível da atividade.';
$string['youtube_visibility_public'] = 'Público';
$string['youtube_visibility_unlisted'] = 'Não listado';
$string['youtube_visibility_private'] = 'Privado';
$string['zoom_recording_delete_days'] = 'Excluir gravações Zoom após';
$string['zoom_recording_delete_days_desc'] = 'Dias após o envio ao YouTube para excluir automaticamente a gravação na nuvem Zoom. Vazio = nunca excluir.';
$string['zoom_recording_delete_days_help'] = 'Após envio bem-sucedido ao YouTube, a gravação original na nuvem Zoom pode ser excluída para economizar armazenamento.';
$string['never_delete'] = 'Nunca excluir';

// Storage settings.
$string['storage_settings'] = 'Armazenamento de vídeo';
$string['storage_settings_desc'] = 'Configure armazenamento temporário para downloads de vídeo.';
$string['temp_directory'] = 'Diretório temporário';
$string['temp_directory_desc'] = 'Diretório para armazenamento temporário. Vazio = temp padrão do Moodle.';
$string['temp_storage_limit'] = 'Limite de espaço';
$string['temp_storage_limit_desc'] = 'Espaço máximo em disco para armazenamento temporário.';
$string['insufficient_disk_space'] = 'Espaço em disco insuficiente para download do vídeo.';
$string['cannot_create_file'] = 'Não foi possível criar o arquivo: {$a}';
$string['download_failed'] = 'Falha no download: {$a}';

// Scheduled tasks.
$string['task_sync_recordings_youtube'] = 'Sincronizar gravações Zoom com o YouTube';
$string['task_sync_alternative_hosts'] = 'Sincronizar instrutores do curso como anfitriões alternativos';
$string['task_retry_transcript_downloads'] = 'Tentar novamente download de transcrições do YouTube';
$string['task_fetch_recordings_adhoc'] = 'Buscar gravações Zoom (acionado por webhook)';
$string['task_sync_youtube_adhoc'] = 'Sincronizar gravações Zoom com o YouTube (acionado por webhook)';
$string['task_fetch_reports_adhoc'] = 'Buscar relatórios de reuniões Zoom (acionado por webhook)';

// Events.
$string['event_video_uploaded'] = 'Vídeo enviado ao YouTube';
$string['event_video_viewed'] = 'Vídeo visualizado';
$string['event_youtube_connected'] = 'Canal YouTube conectado';

// Video gallery.
$string['video_gallery'] = 'Gravações da sessão';
$string['no_videos'] = 'Nenhuma gravação disponível para esta atividade.';
$string['video_status_pending'] = 'Processando';
$string['video_status_downloading'] = 'Baixando do Zoom';
$string['video_status_uploading'] = 'Enviando ao YouTube';
$string['video_status_uploaded'] = 'Disponível';
$string['video_status_failed'] = 'Falha no envio';
$string['view_on_youtube'] = 'Ver no YouTube';
$string['video_status_deleted'] = 'Excluído do YouTube';

// Ação de exclusão no YouTube.
$string['delete_from_youtube'] = 'Excluir do YT';
$string['delete_from_youtube_confirm'] = 'Isso excluirá permanentemente o vídeo do YouTube. A sessão do Zoom continuará listada abaixo. Continuar?';
$string['delete_youtube_success'] = 'O vídeo foi excluído do YouTube.';
$string['delete_youtube_error'] = 'Não foi possível excluir o vídeo do YouTube: {$a}';

// Ação para adicionar um vídeo do YouTube.
$string['add_youtube_video'] = 'Adicionar vídeo do YouTube';
$string['add_youtube_video_help'] = 'Cole a URL ou o ID de um vídeo do YouTube. O vídeo será adicionado às gravações da sessão e ficará visível para os alunos.';
$string['youtube_url'] = 'URL do YouTube';
$string['add_video_success'] = 'O vídeo do YouTube foi adicionado às gravações da sessão.';
$string['add_video_invalid_url'] = 'Isso não parece ser uma URL ou ID de vídeo do YouTube válido.';
$string['add_video_already_exists'] = 'Esse vídeo do YouTube já foi adicionado.';
$string['manual_video_default_title'] = 'Vídeo do YouTube';
$string['session_date'] = 'Data da sessão';
$string['video_duration'] = 'Duração';
$string['toggle_video_visibility'] = 'Alternar visibilidade';
$string['visibility'] = 'Visibilidade';
$string['video_visible'] = 'Visível para alunos';
$string['video_hidden'] = 'Oculto dos alunos';
$string['video_updated'] = 'Vídeo atualizado com sucesso';
$string['video_synced'] = 'Vídeo sincronizado do YouTube';
$string['view_tile'] = 'Visualização em blocos';
$string['view_list'] = 'Visualização em lista';
$string['zoom_recording_status'] = 'Gravação Zoom';
$string['youtube_status'] = 'Status YouTube';
$string['click_to_edit'] = 'Clique para editar';
$string['edit_video'] = 'Editar vídeo';
$string['title'] = 'Título';
$string['captions_available'] = 'Legendas disponíveis';
$string['session_recordings'] = 'Gravações da sessão';
$string['next_meeting'] = 'Próxima reunião: {$a}';
$string['view_recorded_sessions'] = 'Ver sessões gravadas';
$string['no_scheduled_meetings'] = 'Nenhuma reunião agendada';
$string['transcript'] = 'Transcrição';
$string['download_transcript'] = 'Baixar transcrição';
$string['download_transcripts'] = 'Baixar transcrições do YouTube';
$string['download'] = 'Baixar';
$string['transcripts_downloaded'] = 'Transcrições baixadas com sucesso';
$string['no_transcripts_available'] = 'Nenhuma transcrição disponível para este vídeo';
$string['transcript_download_error'] = 'Falha ao baixar transcrições: {$a}';
$string['pending_sync'] = 'Sincronização pendente';
$string['transcripts_sync_hint'] = 'As transcrições serão baixadas ao clicar em "Sincronizar com o YouTube"';
$string['recording_available'] = 'Disponível';
$string['recording_not_available'] = 'Indisponível';
$string['recording_uploaded'] = 'Enviado';
$string['recording_pending'] = 'Envio pendente';
$string['manage_recordings'] = 'Gerenciar gravações';
$string['sync_recordings_button'] = 'Verificar gravações no Zoom';
$string['sync_youtube_button'] = 'Sincronizar com o YouTube';
$string['sync_recordings_success'] = 'Verificação de novas gravações na nuvem Zoom concluída.';
$string['sync_recordings_error'] = 'Erro ao verificar gravações: {$a}';
$string['sync_reports_success'] = 'Dados de sessão obtidos do Zoom com sucesso.';
$string['sync_reports_error'] = 'Erro ao buscar relatórios: {$a}';
$string['sync_youtube_success'] = 'Sincronização com o YouTube iniciada com sucesso.';
$string['sync_youtube_error'] = 'Erro ao sincronizar com o YouTube: {$a}';
$string['activity_video_visibility'] = 'Visibilidade padrão do vídeo';
$string['activity_video_visibility_help'] = 'Se os vídeos desta atividade devem ser públicos ou não listados no YouTube.';

// Completion strings.
$string['completionattendance'] = 'Exigir duração de presença';
$string['completionattendance_help'] = 'Minutos mínimos na reunião para concluir a atividade. 0 desativa.';
$string['completionattendance_desc'] = 'Participar por pelo menos {$a} minutos';
$string['completionwatchpercent'] = 'Exigir percentual de visualização do vídeo';
$string['completionwatchpercent_help'] = 'Percentual mínimo do vídeo que o aluno deve assistir. O tempo de reprodução é acumulado. 0 desativa. Alternativa à presença ao vivo — pode concluir participando ao vivo ou assistindo à gravação.';
$string['completionwatchpercent_desc'] = 'Assistir a pelo menos {$a}% da gravação';

// Video player strings.
$string['rewind30'] = 'Voltar 30 segundos';
$string['forward30'] = 'Avançar 30 segundos';
$string['prevchapter'] = 'Capítulo anterior';
$string['nextchapter'] = 'Próximo capítulo';
$string['searchtranscript'] = 'Buscar na transcrição';
$string['notranscriptavailable'] = 'Nenhuma transcrição disponível';
$string['loadingtranscript'] = 'Carregando transcrição...';
$string['watchprogress'] = 'Progresso de visualização';
$string['resumefrom'] = 'Retomar de {$a}';
$string['transcriptlanguage'] = 'Idioma da transcrição';

// Activity-level YouTube strings.
$string['youtube_activity_header'] = 'Integração YouTube';
$string['yt_use_inherited'] = 'Usar canal YouTube herdado';
$string['yt_use_inherited_help'] = 'Quando ativado, as gravações vão para o canal configurado na categoria ou no site. Desative para um canal só desta atividade.';
$string['youtube_inherited_channel'] = 'Canal herdado';
$string['yt_channel_connected'] = 'Conectado: {$a}';
$string['fromcategory'] = 'da categoria';
$string['fromsite'] = 'do site';
$string['yt_no_channel_configured'] = 'Nenhum canal YouTube configurado';
$string['yt_activity_channel'] = 'Canal YouTube da atividade';
$string['yt_activity_channel_not_connected'] = 'Nenhum canal conectado para esta atividade';
$string['yt_current_channel'] = 'Canal atual';
$string['yt_save_activity_first'] = 'Salve a atividade primeiro; depois você pode conectar um canal YouTube.';
$string['yt_activity_visibility'] = 'Visibilidade no YouTube';
$string['yt_activity_visibility_help'] = 'Visibilidade padrão dos vídeos enviados desta atividade ao YouTube.';

// Webhook strings.
$string['webhook_settings'] = 'Webhooks';
$string['webhook_settings_desc'] = 'Configure webhooks Zoom para atualizações em tempo real. Quando ativado, o Zoom notifica o Moodle ao encerrar reuniões ou quando gravações ficam prontas, sem esperar apenas pelas tarefas agendadas.';
$string['webhook_enabled'] = 'Ativar webhooks';
$string['webhook_enabled_desc'] = 'Quando ativado, o Moodle processa eventos de webhook do Zoom para presença e gravações imediatas.';
$string['webhook_url'] = 'URL do endpoint do webhook';
$string['webhook_url_desc'] = 'Informe esta URL nas assinaturas de evento do app Zoom.';
$string['webhook_secret'] = 'Token secreto do webhook';
$string['webhook_secret_desc'] = 'Secret Token nas configurações do app Zoom. Usado para verificar que as requisições são do Zoom.';

// Event strings.
$string['event_attendance_recorded'] = 'Presença registrada';
$string['event_webhook_meeting_ended'] = 'Webhook: reunião encerrada';
$string['event_webhook_recording_ready'] = 'Webhook: gravação pronta';
$string['event_recording_discovered'] = 'Gravação descoberta';
$string['event_meeting_created'] = 'Reunião criada';
$string['event_meeting_updated'] = 'Reunião atualizada';

// Retry upload strings.
$string['retry_upload'] = 'Tentar envio novamente';
$string['retry_upload_success'] = 'Nova tentativa de envio ao YouTube concluída com sucesso.';
$string['retry_upload_error'] = 'Falha na nova tentativa de envio ao YouTube: {$a}';

// Recorrência personalizada e interpretação (v2.6.16).
$string['recurrence_option_custom'] = 'Datas personalizadas (lista de sessões)';
$string['customdates_help'] = 'Adicione cada sessão com data/hora e duração (minutos). O Zoom fornece um único link; o Moodle armazena a agenda e os eventos do calendário.';
$string['customdates_add'] = 'Adicionar sessão';
$string['customdates_remove'] = 'Remover';
$string['customdates_sessions'] = 'Sessões agendadas';
$string['err_customdates_required'] = 'Adicione pelo menos uma sessão para recorrência personalizada.';
$string['err_customdates_past'] = 'Ao criar a atividade, cada sessão deve começar hoje ou depois.';
$string['interpretation'] = 'Interpretação';
$string['interpretation_spoken_header'] = 'Interpretação falada';
$string['interpretation_sign_header'] = 'Interpretação em língua de sinais';
$string['interpretation_enable'] = 'Interpretação falada';
$string['interpretation_enable_label'] = 'Ativar interpretação falada';
$string['interpretation_enable_help'] = 'Quando ativada, os intérpretes podem transmitir um canal de áudio traduzido durante a reunião. Cada intérprete deve ser um usuário licenciado na sua conta Zoom. Para cada intérprete escolha os dois idiomas que ele traduz (de / para).';
$string['sign_interpretation_enable'] = 'Interpretação em língua de sinais';
$string['sign_interpretation_enable_label'] = 'Ativar interpretação em língua de sinais';
$string['sign_interpretation_enable_help'] = 'A interpretação em língua de sinais é um recurso Zoom separado da interpretação falada. Cada intérprete usa uma única língua de sinais e deve ser um usuário ativo na sua conta Zoom.';
$string['breakoutrooms_enable'] = 'Permitir salas de grupo durante a reunião';
$string['breakoutrooms_enable_desc'] = 'O anfitrião pode criar salas de grupo sem defini-las aqui. Coanfitriões podem ajudar se a conta Zoom permitir.';
$string['interpretation_notice_spoken'] = 'Interpretação falada disponível nesta reunião:';
$string['interpretation_notice_sign'] = 'Interpretação em língua de sinais disponível nesta reunião:';

// v2.7.2 additions.
$string['customdates_timezone'] = 'Os horários abaixo estão no seu fuso horário: {$a}';
$string['interpretation_email'] = 'E-mail do intérprete';
$string['interpretation_email_placeholder'] = 'nome@exemplo.com';
$string['interpretation_email_help'] = 'O e-mail deve corresponder a um usuário ativo na sua conta Zoom; caso contrário, o Zoom rejeitará a criação/atualização.';
$string['interpretation_language'] = 'Idioma';
$string['interpretation_lang_from'] = 'De';
$string['interpretation_lang_to'] = 'Para';
$string['interpretation_none'] = 'Nenhum intérprete adicionado ainda.';
$string['interpretation_add_interpreter'] = 'Adicionar intérprete';
$string['sign_interp_lang'] = 'Língua de sinais';
$string['err_interpretation_email'] = 'Informe um e-mail de intérprete válido (usuário Zoom da sua conta).';
$string['err_interpretation_lang_required'] = 'Escolha os idiomas De e Para deste intérprete.';
$string['err_interpretation_same_languages'] = 'Os idiomas De e Para devem ser diferentes.';
$string['err_interpretation_signlang'] = 'Selecione uma língua de sinais para este intérprete.';
$string['err_interpretation_required'] = 'Adicione pelo menos um intérprete ou desative a interpretação.';
$string['err_interpretation_duplicate'] = 'Este e-mail de intérprete já está na lista.';
$string['interp_lang_us'] = 'Inglês';
$string['interp_lang_cn'] = 'Chinês';
$string['interp_lang_jp'] = 'Japonês';
$string['interp_lang_de'] = 'Alemão';
$string['interp_lang_fr'] = 'Francês';
$string['interp_lang_ru'] = 'Russo';
$string['interp_lang_pt'] = 'Português';
$string['interp_lang_es'] = 'Espanhol';
$string['interp_lang_kr'] = 'Coreano';
$string['sign_lang_american'] = 'Língua de sinais americana';
$string['sign_lang_chinese'] = 'Língua de sinais chinesa';
$string['sign_lang_french'] = 'Língua de sinais francesa';
$string['sign_lang_german'] = 'Língua de sinais alemã';
$string['sign_lang_italian'] = 'Língua de sinais italiana';
$string['sign_lang_japanese'] = 'Língua de sinais japonesa';
$string['sign_lang_korean'] = 'Língua de sinais coreana';
$string['sign_lang_portuguese'] = 'Língua de sinais portuguesa';
$string['sign_lang_russian'] = 'Língua de sinais russa';
$string['sign_lang_spanish'] = 'Língua de sinais espanhola';