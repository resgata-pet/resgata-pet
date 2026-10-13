# Módulo Pedidos de adoção

Código em `src/Modules/Adoptions/`. Depende do módulo Animais.

## Fluxo

1. A pessoa preenche o questionário na página do animal.
2. O pedido é gravado na fila interna.
3. A tela de confirmação mostra o botão "Enviar pedido pelo WhatsApp", que abre a conversa com
   o WhatsApp das Configurações já com o pedido escrito (`Messages::whatsapp_text()`).
4. Se houver `adoption_email` nas Configurações, a protetora também recebe um e-mail só com
   nome, animal e link do painel. As respostas ficam fora do e-mail.

## Dados

- CPT `resgata_adoption`, **não público**: sem tela no wp-admin, fora da REST do WordPress e das
  buscas. Status de post `private`; o título é o nome de quem pediu.
- Metas: `resgata_adoption_status` (`new`, `reviewing`, `interview`, `approved`, `declined`),
  `resgata_adoption_animal_id`, `resgata_adoption_answers`, `resgata_adoption_consent` (data + texto exato
  aceito) e `resgata_adoption_notes` (anotações internas).
- Capability `resgata_manage_adoptions`: Administrador e Protetor admin (o Editor comum não).
- Excluir um pedido apaga em definitivo (não há lixeira).

## Questionário

`Questionnaire::fields()` (filtro `resgata_adoption_questionnaire_fields`) é a definição única das
perguntas: ela monta o formulário, valida, alimenta o painel e a mensagem do WhatsApp.
Pergunta nova entra só ali.

## Formulário e envio

- Bloco `resgata-wp/adoption-form` (templates `adoption-form.php` e `adoption-success.php`),
  já incluído nos modelos da página do animal. Só aparece para animal publicado e
  "disponível". Em outra página, mostra um seletor de animais disponíveis (`?resgata_animal=ID`
  pré-seleciona).
- Envio (`Submission`): POST na própria página, nonce, campo-armadilha contra robôs, limite de
  5 pedidos aceitos por hora por endereço de rede (guardado só como resumo irreversível) e
  redirecionamento com código de uso único (`?resgata_pedido=`) para a confirmação.
- Consentimento (LGPD): caixa obrigatória. O texto vem de "Texto de privacidade" nas
  Configurações (ou de um texto padrão) e é guardado junto com o pedido.

## Painel

- `/painel/adocoes`: lista com filtro por situação e troca rápida.
- `/painel/adocoes/:id`: respostas, contato, animal, consentimento e anotações internas.
- O menu mostra o número de pedidos novos.
