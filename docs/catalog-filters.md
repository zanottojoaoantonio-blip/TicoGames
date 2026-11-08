# Pesquisa, filtros e paginação

catalog.js consulta produtos com Axios; o Laravel aplica pesquisa, filtros, ordenação e paginação antes de devolver a resposta. O navegador exibe apenas a página retornada.

## Parâmetros

| Parâmetro da API | Uso |
| --- | --- |
| q | Pesquisa no nome |
| category_id | Uma categoria |
| platform_id | Uma plataforma |
| genre_id | Um gênero associado |
| sort | id, price_asc, price_desc ou newest |
| page | Página solicitada |
| per_page | 1 a 100; padrão 12 |

Categorias, plataformas e gêneros são consultados em suas rotas próprias, incluindo opções sem produto na página atual. A carga possui Jogos, Consoles e Acessórios. Os links Games/Consoles resolvem a categoria pelo nome, enquanto as consultas usam seu ID.

Filtros são combinados. Cada alteração reinicia a página; próxima/anterior faz nova consulta. O servidor devolve data, links e meta com total/current_page/last_page. Visitantes e clientes só recebem produtos ativos; admin também vê inativos.

## Estado na URL

URLSearchParams guarda pesquisa, IDs dos filtros, ordenação e página. history.replaceState atualiza a URL sem recarregar. Ao abrir a página, a seleção é restaurada; links de outras telas podem usar category=Jogos ou category=Consoles.

Uma sequência de consultas impede resposta atrasada de sobrescrever a mais recente. Erro limpa o resultado e desabilita paginação; não apresenta uma seleção antiga como resultado atual.

Cards usam textContent, imagem alternativa e botões desabilitados para produto indisponível. Estoque e preço são novamente conferidos pelo backend ao incluir/criar pedido.

Testes: `node --test front/tests/catalog.test.cjs`. Veja [CRUD de produtos](crud-products.md).
