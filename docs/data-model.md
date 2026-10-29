# Etapa 1 — Modelagem de dados

Etapa 1 concluída em 28/10/2026, após revisar as regras com o usuário. A busca terá filtros por gênero e plataforma. O termo “porte de console” foi esclarecido: significa a plataforma, como PS5, Xbox Series ou Nintendo Switch. Os Models foram revisados na etapa 3 e as tabelas foram criadas na etapa 4; as funcionalidades ainda serão implementadas nas etapas próprias. Os detalhes da estrutura física estão em [migrations.md](migrations.md).

## Como entender a estrutura

Uma tabela guarda registros de um mesmo assunto. Cada produto, por exemplo, será um registro em `products`. Os relacionamentos conectam esses registros: `products.platform_id` indica a plataforma de um produto.

- **Um para muitos:** uma plataforma possui vários produtos, mas cada versão de um produto pertence a uma plataforma.
- **Muitos para muitos:** um produto possui várias categorias e cada categoria agrupa vários produtos. A tabela `category_product` guarda essas associações, sem duplicar os dados do produto. A mesma ideia vale para `genre_product`.
- **Dados históricos:** os itens do pedido guardam o nome e o preço da compra; o pedido guarda o endereço escolhido. Essas cópias preservam a compra quando o catálogo ou o cadastro de endereços mudar.

Categorias e gêneros ficam separados: categorias organizam o catálogo, como Jogos, Consoles e Lançamentos; gêneros descrevem os jogos, como Ação, Aventura e RPG. Uma lista única teria menos tabelas, mas separar os conceitos deixa o filtro por gênero mais claro.

Exemplo: “Jogo Exemplo — PS5” pode ter as categorias Jogos e Lançamentos e os gêneros Ação e RPG. A versão “Jogo Exemplo — Xbox Series” será outro produto, com preço e estoque próprios, mesmo que compartilhe categorias e gêneros.

## Escopo aprovado

- Loja de jogos físicos e consoles.
- Cada versão de um jogo para uma plataforma é um produto independente, com preço e estoque próprios.
- Cada produto pode pertencer a várias categorias e a uma plataforma. Jogos podem ter vários gêneros, usados como filtro de busca.
- A busca por console usa a plataforma do produto: PS5, Xbox Series, Nintendo Switch etc. Não haverá um filtro adicional de console de mesa, portátil ou híbrido.
- Dois perfis em `users.role`: `customer` e `admin`.
- Catálogo e carrinho disponíveis para visitantes; login obrigatório para criar pedidos.
- Pagamento simulado: o próprio cliente confirma o pagamento de seu pedido, como se tivesse acabado de pagar por cartão ou Pix.
- A simulação oferece apenas confirmar ou cancelar. Não há escolha nem registro da forma de pagamento.
- O estoque diminui quando a confirmação do cliente é aceita pelo backend.
- Status: `pending_payment`, `paid` e `cancelled`.
- O cliente pode cancelar um pedido próprio que esteja em `pending_payment`.
- A tela do pedido pendente oferece duas caixas de ação, uma abaixo da outra: confirmar pagamento e cancelar compra. São ações alternativas sobre o mesmo pedido, não duas caixas de seleção independentes.
- No resumo do carrinho/compra, clicar em confirmar abre uma confirmação com o texto “Confirme a compra”; clicar em cancelar abre “Você quer mesmo cancelar?”. Cada confirmação permite voltar ou concluir a ação escolhida. Apenas abrir ou fechar essa confirmação não altera o pedido nem o estoque. A tela será implementada na etapa 9.
- O cliente cadastra um endereço por vez, pode manter vários endereços salvos e seleciona exatamente um ao finalizar uma compra.

## Decisões de simplificação

- Visitantes mantêm o carrinho no `localStorage` do navegador. Não há compartilhamento entre navegadores ou dispositivos para visitantes.
- Clientes autenticados mantêm um carrinho no banco. Sair da conta preserva esse carrinho, mas não o expõe ao próximo visitante.
- No login, os itens do carrinho de visitante são incorporados ao carrinho da conta. Produtos iguais têm as quantidades somadas; a disponibilidade é validada antes de finalizar a compra.
- Após a incorporação bem-sucedida, os itens transferidos saem do carrinho de visitante para não serem somados novamente no próximo login. O carrinho da conta permanece no banco ao sair.
- Carrinhos e pedidos sem pagamento não reservam estoque. A disponibilidade é verificada ao criar o pedido e novamente ao confirmar o pagamento.
- Se faltar estoque na confirmação, o cliente recebe a informação e o pedido continua aguardando pagamento, sem baixa parcial de itens.
- Confirmação de pagamento e baixa de todos os itens devem ocorrer em uma única transação, protegendo o estoque contra confirmações simultâneas.
- O cliente só pode confirmar ou cancelar seus próprios pedidos pendentes. Pedidos pagos ou cancelados não aceitam uma segunda decisão. Confirmação e cancelamento concorrentes devem resultar em apenas uma decisão aceita.
- Preços e totais usam `decimal(10,2)`, sem ponto flutuante.
- Preços e disponibilidade são conferidos pelo backend; valores enviados pelo navegador não são considerados confiáveis.
- Produtos deixam de ser oferecidos por meio de `is_active`, preservando os vínculos com pedidos anteriores.
- Não há tabela de pagamentos, vendedores, variações ou perfis de usuário separados.
- Não há cálculo de frete nesta etapa. Caso seja necessário depois, sua regra será definida antes de implementá-lo.

## Ideia guardada para depois

Cancelar automaticamente pedidos sem pagamento após um prazo. Foi sugerido um prazo de 24 horas, mas tanto o prazo quanto a implementação ficam adiados. Não criar agendador ou lógica de expiração nas próximas etapas sem retomar essa decisão.

## Convenções

- `id`: identificador da linha.
- Campos terminados em `_id`: referências a outras tabelas.
- Todas as tabelas possuem `created_at` e `updated_at`, exceto as tabelas de ligação `category_product` e `genre_product`.
- Quantidades e estoque são inteiros; quantidade de item deve ser maior que zero e estoque não pode ficar negativo. Preços e totais também não podem ser negativos.
- Complementos de endereço são opcionais. CEP e número são texto para preservar zeros iniciais e permitir números como `12A` ou `s/n`.
- Senhas são armazenadas como hash pelo Laravel.
- As migrations usam inteiros sem sinal para estoque e quantidades. Quantidade mínima 1, preços não negativos e ao menos uma categoria por produto serão validados nos FormRequests da etapa 7; essas regras não são totalmente impostas pelos tipos das colunas. Detalhes em `docs/migrations.md`.

## Tabelas

São 12 tabelas de negócio, contando `users` e as duas tabelas de ligação. Tabelas técnicas padrão do Laravel, como as de sessões e recuperação de senha, não entram nessa contagem.

### users

`id`, `name`, `email`, `email_verified_at` (opcional), `password`, `remember_token` (opcional), `role`.

Reutiliza o usuário padrão do Laravel, acrescentando o perfil. Email é único. `role` aceita apenas `customer` e `admin`, com padrão `customer`. Novos registros públicos sempre recebem `customer`; o cliente não escolhe o próprio perfil.

### addresses

`id`, `user_id`, `recipient_name`, `postal_code`, `street`, `number`, `complement` (opcional), `neighborhood`, `city`, `state`.

Um usuário pode cadastrar vários endereços. A interface pode exibi-los como cartões de seleção. O usuário só pode consultar ou alterar seus próprios endereços.

### categories

`id`, `name`.

Nome único. Cada produto possui uma ou mais categorias. As categorias organizam o catálogo e os gêneros, como Ação e RPG, ficam na lista separada `genres` para a busca.

### category_product

`category_id`, `product_id`.

Tabela de ligação entre categorias e produtos: uma categoria possui vários produtos e um produto possui várias categorias. O par deve ser único. Não precisa de `id` próprio nem de timestamps. Esta relação substitui o campo `category_id` direto em `products`.

### platforms

`id`, `name`.

Nome único. Exemplos: PlayStation 5, Xbox Series e Nintendo Switch. Cada produto pertence a uma plataforma, inclusive os próprios consoles.

### genres

`id`, `name`.

Nome único. Exemplos: Ação, Aventura e RPG. Um jogo pode ter vários gêneros; consoles não precisam ter nenhum. A busca poderá filtrar produtos por gênero.

### products

`id`, `platform_id`, `name`, `description`, `price`, `image_path`, `stock`, `is_active`.

`price` usa `decimal(10,2)`; `stock` é inteiro; `is_active` é booleano, com padrão verdadeiro. A imagem é um caminho de arquivo, não o conteúdo da imagem no banco. Cada produto tem exatamente uma plataforma; as categorias e os gêneros são ligados pelas tabelas intermediárias.

### genre_product

`genre_id`, `product_id`.

Tabela de ligação entre gêneros e produtos. O par deve ser único, impedindo que o mesmo gênero seja atribuído duas vezes ao mesmo produto. Não precisa de `id` próprio nem de timestamps.

### carts

`id`, `user_id`.

`user_id` é único: cada cliente tem no máximo um carrinho persistente. Visitantes não geram linhas nessa tabela.

### cart_items

`id`, `cart_id`, `product_id`, `quantity`.

O par `cart_id` + `product_id` é único. Um produto aparece uma única vez no carrinho, com sua quantidade. O preço vem do cadastro atual do produto.

### orders

`id`, `user_id`, `status`, `total`, `shipping_recipient_name`, `shipping_postal_code`, `shipping_street`, `shipping_number`, `shipping_complement` (opcional), `shipping_neighborhood`, `shipping_city`, `shipping_state`.

O pedido guarda uma cópia do endereço escolhido. Editar ou excluir um endereço salvo não modifica a entrega de um pedido existente. `total` é calculado pelo backend pela soma de quantidade multiplicada pelo preço unitário dos itens.

O campo `status` aceita somente `pending_payment`, `paid` e `cancelled`, com padrão `pending_payment`. `total` usa `decimal(10,2)`. A simulação não oferece escolha de Pix ou cartão e não registra a forma de pagamento; portanto, não há campo `payment_method`.

### order_items

`id`, `order_id`, `product_id`, `product_name`, `unit_price`, `quantity`.

`product_name` e `unit_price` são cópias dos dados do produto no momento da criação do pedido. `unit_price` usa `decimal(10,2)` e `quantity` é inteiro positivo. Alterações futuras no catálogo não mudam o histórico da compra. O par `order_id` + `product_id` é único.

## Relacionamentos

- Um usuário tem vários endereços e pedidos e no máximo um carrinho.
- Uma categoria tem vários produtos e um produto tem várias categorias, através de `category_product`.
- Uma plataforma tem vários produtos.
- Um produto pode ter vários gêneros e um gênero pode ter vários produtos, através de `genre_product`.
- Um carrinho tem vários itens; cada item aponta para um produto.
- Um pedido tem vários itens; cada item aponta para um produto e preserva nome e preço históricos.
- O endereço do pedido é uma cópia; não depende do endereço salvo continuar existindo.

## Fluxo do pedido

| Status atual | Ação do cliente | Resultado | Estoque |
| --- | --- | --- | --- |
| `pending_payment` | Confirmar pagamento com disponibilidade | `paid` | Diminui uma única vez. |
| `pending_payment` | Confirmar pagamento sem disponibilidade | Continua `pending_payment` | Não muda. |
| `pending_payment` | Cancelar compra | `cancelled` | Não muda. |
| `paid` ou `cancelled` | Tentar uma nova confirmação ou cancelamento | Mantém o status atual | Não muda. |

1. O cliente autenticado escolhe um endereço e finaliza o carrinho.
2. O backend valida produtos ativos, preços e quantidades, cria o pedido com status `pending_payment`, copia o endereço e os dados dos itens e limpa o carrinho após o sucesso.
3. O estoque ainda não diminui.
4. A tela apresenta os itens, o total e duas caixas de ação empilhadas: **Confirmar compra** e **Cancelar compra**. Cada ação abre sua confirmação: “Confirme a compra” ou “Você quer mesmo cancelar?”. O cliente pode voltar ao resumo sem executar a ação. A confirmação da compra representa o pagamento simulado.
5. Ao concluir a confirmação da compra, o backend verifica se o pedido é do cliente e ainda está pendente, confere novamente o estoque de todos os itens e, se houver disponibilidade, reduz as quantidades e muda o pedido para `paid` na mesma transação.
6. Confirmar novamente um pedido já pago não reduz o estoque outra vez. Se qualquer item estiver sem estoque suficiente, nenhuma baixa ocorre e o pedido permanece em `pending_payment`.
7. Ao cancelar, o backend verifica se o pedido é do cliente e ainda está pendente e o muda para `cancelled`. Como não houve baixa, não há estoque para devolver. Não é possível confirmar um pedido cancelado nem cancelar um pedido pago.

Reembolso e cancelamento após pagamento não fazem parte do fluxo atual.

## Verificações realizadas nesta etapa

- [x] Regras revisadas com o usuário, incluindo o significado de plataforma.
- [x] Campos, relacionamentos e restrições documentados para as 12 tabelas.
- [x] Várias categorias por produto representadas por uma tabela de ligação.
- [x] Pagamento confirmado pelo cliente e somente três status registrados na documentação.
- [x] Documentação revisada com `git diff --check`, sem erros de espaços em branco.

## Checklist de testes para as próximas etapas

Os itens abaixo descrevem comportamentos que serão testados depois da implementação; ainda não são funcionalidades verificadas.

- [ ] O mesmo jogo para duas plataformas tem preços e estoques independentes.
- [ ] Um produto pode ter várias categorias sem duplicar preço ou estoque.
- [ ] A busca permite filtrar produtos por gênero e console.
- [ ] Cada produto tem exatamente uma plataforma e o catálogo aceita combinar filtros de gênero e plataforma.
- [ ] O carrinho de visitante permanece ao recarregar ou reabrir o navegador, com o armazenamento local preservado.
- [ ] No login, os itens do visitante são incorporados ao carrinho da conta.
- [ ] Sair da conta preserva o carrinho do cliente sem expô-lo ao visitante.
- [ ] Um cliente tem vários endereços e seleciona um para o pedido.
- [ ] Alterar o endereço ou o preço de um produto não modifica pedidos existentes.
- [ ] Criar um pedido não reduz estoque; confirmar pagamento reduz uma única vez.
- [ ] O cliente confirma seu próprio pagamento simulado; o administrador não precisa confirmar.
- [ ] A simulação oferece apenas confirmar ou cancelar, sem registrar a forma de pagamento.
- [ ] Confirmar e cancelar aparecem como duas caixas de ação empilhadas.
- [ ] Cada ação abre sua confirmação com a mensagem correspondente; voltar ao resumo não altera pedido nem estoque.
- [ ] Somente os status `pending_payment`, `paid` e `cancelled` são aceitos.
- [ ] Pagamento não é confirmado se algum item estiver sem estoque suficiente.
- [ ] Cancelar um pedido sem pagamento não aumenta o estoque.
- [ ] Duas confirmações simultâneas não reduzem o estoque duas vezes; confirmação e cancelamento simultâneos não são ambos aceitos.
- [ ] Um cliente não pode ver ou alterar carrinhos, endereços ou pedidos de outro cliente.
- [ ] Expiração automática permanece adiada.

## Estado da revisão

- A etapa 1 foi concluída. A busca usará categorias, gêneros e plataformas; o usuário confirmou que “porte” significa plataforma.
- A forma de pagamento não será registrada. O cliente terá apenas as ações de confirmar o pagamento simulado e cancelar a compra enquanto o pedido estiver pendente.
- Na revisão da etapa 1 foram alterados somente documentos. As regras automáticas de pagamento e as telas ainda não foram implementadas.
- Na etapa 3, os 10 Models foram revisados. Na etapa 4, as tabelas foram criadas e as relações, casts e restrições foram verificadas no MySQL com dados temporários. As cópias históricas foram preenchidas manualmente para a verificação; seu preenchimento automático pertence ao fluxo de pedidos nas etapas de CRUD.

As etapas 1 a 5 estão concluídas. A etapa 6 possui os quatro CRUDs do catálogo concluídos, conforme `docs/crud-catalog.md`; o percurso das requisições de produtos está em `docs/crud-products.md`. Os fluxos de contas, endereços, carrinho e pedidos ainda serão implementados. Aguardar “próximo” antes de outra etapa. O contexto atualizado está em `docs/gpt.md`.
