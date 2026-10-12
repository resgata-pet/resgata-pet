# Módulo Animais

Código em `src/Modules/Animals/`.

## Dados

- CPT `rwp_animal` (arquivo `/animais/`, REST `wp/v2/animais`), com capabilities próprias
  (`edit_rwp_animals`...), concedidas a Administrador e Editor na ativação.
- Taxonomias: `rwp_species` (escolhida num select da ficha; fora da REST) e `rwp_temperament`
  (etiquetas).
- Metas (`Meta.php`, todas com `show_in_rest => false`): `rwp_status`, `rwp_sex`, `rwp_size`,
  `rwp_color`, `rwp_birth_date`, `rwp_rescue_date`, `rwp_gallery`, `rwp_vaccines`,
  `rwp_dewormed_date`, `rwp_neutered`, `rwp_fiv`, `rwp_felv`, `rwp_tagline` (frase de
  apresentação), `rwp_intro` (resumo), `rwp_weight` (kg), `rwp_vet_checked` e
  `rwp_vaccinated` (sim / ainda não).
- **A situação do animal é a meta `rwp_status`**, e não o status do post. A lista pública
  mostra, por padrão, `available` e `reserved`.
- Idade: o cadastro pede anos e meses e grava a data de nascimento estimada, que só muda se a
  idade informada for diferente da calculada.
- Temperamento: caixas de seleção com a lista de `Options::temperaments()` (filtro
  `rwp_temperament_options`); os nomes são gravados como termos de `rwp_temperament`.
- Apagar um animal em definitivo apaga os pedidos de adoção ligados a ele.

## Escrita: só pelo painel

- `AnimalWriter::save()` é o **único caminho de escrita** da ficha, usado pela API do painel.
  As regras de validação ficam em `Meta.php`.
- O cadastro acontece em `/painel/animais`. O CPT e as taxonomias têm `show_ui => false`: o
  wp-admin não tem menu, lista, tela de edição nem atalho na barra de administração para eles
  (`edit.php?post_type=rwp_animal`, `post-new.php` e `edit-tags.php` são recusados pelo
  WordPress). As metaboxes antigas foram removidas de propósito; campo novo entra no
  formulário do painel.

## Site

- Excluir pelo painel manda o animal para a lixeira do WordPress (30 dias). Como não há tela
  no wp-admin, restaurar é por WP-CLI: `wp post untrash <ID>`.
- **Página do animal**: bloco `resgata-wp/animal-details` (template `animal-details.php`,
  dados montados em `Frontend\DetailsRenderer`). Desenha a página inteira (caminho, fotos,
  resumo, história, saúde, "Não pode adotar?" e outros animais) ou uma seção só (atributo
  `section`). A página não mostra localização.
- O botão "Quero adotar" vem do filtro `rwp_animal_adopt_url` (módulo de adoção); os links de
  "Não pode adotar?", do filtro `rwp_animal_help_links`.
- A galeria funciona sem JavaScript (rolagem lateral); `resources/frontend/animal-gallery.ts`
  só acrescenta setas e miniaturas. O mesmo script dá setas à fileira "Outros animais para
  adoção" (`data-rwp-row`): elas aparecem só quando a fileira não cabe na tela, e o tema então
  esconde a barra de rolagem; sem o script, fica a barra.
- **Lista de animais**: bloco `resgata-wp/animals-list` (templates `animals-list.php` e
  `animal-card.php`): filtros, contagem, cartões (foto, situação, nome, resumo de
  `Animal::card_summary()`, até três temperamentos e "Ver perfil") e paginação.
- Também há o shortcode `[rwp_animais]`. Filtros por GET: `rwp_especie`, `rwp_sexo`,
  `rwp_porte`, `rwp_pg`.

## WP-CLI

`wp resgata seed-animals [--count=<n>] [--skip-images] [--delete]` (`make seed` e
`make unseed`).
