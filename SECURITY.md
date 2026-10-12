# Política de segurança

O Resgata WP guarda dados pessoais de quem pede para adotar e de quem apadrinha, e conversa
com a Stripe para receber pagamentos. Por isso, falhas de segurança são tratadas com
prioridade e **em privado**, até existir uma correção.

## Como relatar uma falha

**Não abra issue pública, pull request ou discussão para relatar uma falha.**

Use o relato privado do GitHub: na página do repositório, abra a aba **Security** e clique em
**Report a vulnerability**. Só quem mantém o projeto vê o que você enviar.

Inclua o que puder:

- o que a falha permite fazer e quem consegue explorá-la (visitante, usuário logado, qual
  perfil);
- o passo a passo para reproduzir, de preferência no ambiente local (`make setup`);
- a versão do plugin, do tema e do WordPress;
- o arquivo e o trecho envolvidos, se você já souber;
- uma sugestão de correção, se tiver.

Não inclua dados reais de adotantes ou padrinhos, nem chaves, senhas ou tokens. Se a falha
envolve uma chave, descreva o caminho sem colar o valor.

## O que esperar

- Confirmação de recebimento em até **7 dias**.
- Uma avaliação inicial (se é uma falha, a gravidade e o plano) em até **14 dias**.
- Enquanto a correção é preparada, você é avisado do andamento e pode revisar a correção
  antes da publicação.
- A falha é divulgada depois que a versão corrigida sai. Você recebe o crédito pela
  descoberta, a menos que prefira não aparecer.

O projeto é mantido por voluntários e não tem programa de recompensa.

## Versões com correção

Só a versão mais recente do plugin e do tema recebe correções de segurança. Se o seu site usa
uma versão anterior, atualize.

## O que entra nesta política

- O plugin `resgata-wp` e o tema `resgata-tema` deste repositório.
- Em especial: a API do painel (`resgata-wp/v1`), o webhook da Stripe, o formulário de
  adoção, a área do padrinho (link de acesso e resumo das doações), o registro dos
  pagamentos, o armazenamento das chaves da Stripe e as permissões dos perfis
  (Administrador, Editor e Protetor admin).

Exemplos do que interessa: acesso a pedidos de adoção, padrinhos ou pagamentos sem permissão,
elevação de privilégio, leitura ou troca das chaves da Stripe, falsificação de eventos do
webhook, injeção de SQL, XSS, CSRF e contorno do limite de envios.

## O que não entra

- Falhas no WordPress, em outros plugins ou temas, na Stripe ou na hospedagem: relate a quem
  mantém cada um.
- Problemas que exigem acesso de Administrador ao site ou acesso aos arquivos do servidor.
- O ambiente de desenvolvimento (`docker-compose.yml`), que usa senhas de exemplo de propósito
  e não deve ser exposto à internet.
- Ausência de boas práticas sem impacto demonstrado (por exemplo, cabeçalhos HTTP que
  dependem do servidor).

## Ao pesquisar

- Teste no seu próprio ambiente local. **Não teste em sites de protetoras**: são operações
  reais, com dados de pessoas e pagamentos de verdade.
- Use apenas chaves de sandbox da Stripe.
- Não acesse, altere nem apague dados que não sejam seus.

## Para quem usa o Resgata WP em um site

- Mantenha o WordPress, o plugin e o tema atualizados.
- Na Stripe, use sempre uma **chave restrita** (`rk_`), só com as permissões que o painel
  lista em Configurações → Pagamentos e apadrinhamento.
- Dê o perfil de Administrador a poucas pessoas: quem é Administrador pode trocar a conta
  que recebe os pagamentos. Para o dia a dia, use o perfil **Protetor admin**.
- Se suspeitar que uma chave vazou, revogue-a na Stripe e informe a nova no painel.
- O acesso à área do padrinho é um link enviado por e-mail: use um serviço de envio confiável
  e, se o site tiver cache de página, deixe de fora a página que tem a área do padrinho.
- Faça backup e proteja o arquivo `wp-config.php`: as chaves guardadas pelo painel são
  cifradas com segredos que ficam nele.
