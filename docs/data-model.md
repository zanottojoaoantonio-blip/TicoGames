# Etapa 1 — Modelagem de dados

## Escopo aprovado

- Loja de jogos físicos e consoles.
- Cada versão de um jogo para uma plataforma é um produto independente, com preço e estoque próprios.
- Organização por categoria, plataforma e, para jogos, gêneros.
- Sem classificação adicional entre consoles de mesa, portáteis e híbridos.
- Dois perfis em `users.role`: `customer` e `admin`.
- Catálogo e carrinho disponíveis para visitantes; login obrigatório para criar pedidos.
- Pagamento simulado: o administrador confirma o recebimento, sem integração com meios de pagamento.
- O estoque diminui na confirmação do pagamento.
- Status: `pending_payment`, `paid`, `delivered` e `cancelled`.
- O cliente pode cancelar um pedido próprio que esteja em `pending_payment`.
- O cliente pode salvar vários endereços e selecionar um ao finalizar uma compra.

## Decisões de simplificação

- Visitantes mantêm o carrinho no `localStorage` do navegador. Não há compartilhamento entre navegadores ou dispositivos para visitantes.
- Clientes autenticados mantêm um carrinho no banco. Sair da conta preserva esse carrinho, mas não o expõe ao próximo visitante.
- No login, os itens do carrinho de visitante são incorporados ao carrinho da conta. Produtos iguais têm as quantidades somadas; a disponibilidade é validada antes de finalizar a compra.
- Carrinhos e pedidos sem pagamento não reservam estoque. A disponibilidade é verificada ao criar o pedido e novamente ao confirmar o pagamento.
- Se faltar estoque na confirmação, o administrador não consegue marcar o pedido como pago. O pedido continua aguardando pagamento.
- Confirmação de pagamento e baixa de todos os itens devem ocorrer em uma única transação, protegendo o estoque contra confirmações simultâneas.
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
- Todas as tabelas possuem `created_at` e `updated_at`, exceto a tabela de ligação `genre_product`.
- Quantidades e estoque são inteiros; quantidade de item deve ser maior que zero e estoque não pode ficar negativo.
- Complementos de endereço são opcionais. CEP é texto para preservar zeros iniciais.
- Senhas são armazenadas como hash pelo Laravel.

## Tabelas

### users

`id`, `name`, `email`, `email_verified_at` (opcional), `password`, `remember_token` (opcional), `role`.

Reutiliza o usuário padrão do Laravel, acrescentando o perfil. Email é único. Novos registros públicos sempre recebem `customer`; o cliente não escolhe o próprio perfil.

### addresses

`id`, `user_id`, `recipient_name`, `postal_code`, `street`, `number`, `complement` (opcional), `neighborhood`, `city`, `state`.

Um usuário pode cadastrar vários endereços. A interface pode exibi-los como cartões de seleção. O usuário só pode consultar ou alterar seus próprios endereços.

### categories

`id`, `name`.

Nome único. Exemplos de categorias: Jogos e Consoles. Cada produto pertence a uma categoria.

### platforms

`id`, `name`.

Nome único. Exemplos: PlayStation 5, Xbox Series e Nintendo Switch. Cada produto pertence a uma plataforma, inclusive os próprios consoles.

### genres

`id`, `name`.

Nome único. Exemplos: Ação, Aventura e RPG. Um jogo pode ter vários gêneros; consoles não precisam ter nenhum.

### products

`id`, `category_id`, `platform_id`, `name`, `description`, `price`, `image_path`, `stock`, `is_active`.

`price` é decimal; `stock` é inteiro; `is_active` é booleano. A imagem é um caminho de arquivo, não o conteúdo da imagem no banco.

### genre_product

`genre_id`, `product_id`.

Tabela de ligação entre gêneros e produtos. O par deve ser único, impedindo que o mesmo gênero seja atribuído duas vezes ao mesmo produto. Não precisa de `id` próprio.

### carts

`id`, `user_id`.

`user_id` é único: cada cliente tem no máximo um carrinho persistente. Visitantes não geram linhas nessa tabela.

### cart_items

`id`, `cart_id`, `product_id`, `quantity`.

O par `cart_id` + `product_id` é único. Um produto aparece uma única vez no carrinho, com sua quantidade. O preço vem do cadastro atual do produto.

### orders

`id`, `user_id`, `status`, `total`, `shipping_recipient_name`, `shipping_postal_code`, `shipping_street`, `shipping_number`, `shipping_complement` (opcional), `shipping_neighborhood`, `shipping_city`, `shipping_state`.

O pedido guarda uma cópia do endereço escolhido. Editar ou excluir um endereço salvo não modifica a entrega de um pedido existente. `total` é calculado pelo backend pela soma de quantidade multiplicada pelo preço unitário dos itens.

### order_items

`id`, `order_id`, `product_id`, `product_name`, `unit_price`, `quantity`.

`product_name` e `unit_price` são cópias dos dados do produto no momento da criação do pedido. Alterações futuras no catálogo não mudam o histórico da compra. O par `order_id` + `product_id` é único.

## Relacionamentos

- Um usuário tem vários endereços e pedidos e no máximo um carrinho.
- Uma categoria tem vários produtos.
- Uma plataforma tem vários produtos.
- Um produto tem vários gêneros e um gênero tem vários produtos, através de `genre_product`.
- Um carrinho tem vários itens; cada item aponta para um produto.
- Um pedido tem vários itens; cada item aponta para um produto e preserva nome e preço históricos.
- O endereço do pedido é uma cópia; não depende do endereço salvo continuar existindo.

## Fluxo do pedido

1. O cliente autenticado escolhe um endereço e finaliza o carrinho.
2. O backend valida produtos ativos, preços e quantidades, cria o pedido com status `pending_payment`, copia o endereço e os dados dos itens e limpa o carrinho após o sucesso.
3. O estoque ainda não diminui.
4. O administrador confirma o pagamento. O backend confere novamente o estoque de todos os itens e, se houver disponibilidade, reduz as quantidades e muda o pedido para `paid` na mesma transação.
5. Confirmar novamente um pedido já pago não reduz o estoque outra vez.
6. O administrador pode mudar um pedido de `paid` para `delivered`. Essa mudança não altera estoque.
7. O cliente pode mudar um pedido próprio de `pending_payment` para `cancelled`. Como não houve baixa, não há estoque para devolver.

Reembolso e cancelamento após pagamento não fazem parte do fluxo atual.

## Checklist para validar a modelagem

- [ ] O mesmo jogo para duas plataformas tem preços e estoques independentes.
- [ ] Um jogo aceita vários gêneros e um console pode não ter gênero.
- [ ] O carrinho de visitante permanece ao recarregar ou reabrir o navegador, com o armazenamento local preservado.
- [ ] No login, os itens do visitante são incorporados ao carrinho da conta.
- [ ] Sair da conta preserva o carrinho do cliente sem expô-lo ao visitante.
- [ ] Um cliente tem vários endereços e seleciona um para o pedido.
- [ ] Alterar o endereço ou o preço de um produto não modifica pedidos existentes.
- [ ] Criar um pedido não reduz estoque; confirmar pagamento reduz uma única vez.
- [ ] Pagamento não é confirmado se algum item estiver sem estoque suficiente.
- [ ] Cancelar um pedido sem pagamento não aumenta o estoque.
- [ ] Um cliente não pode ver ou alterar carrinhos, endereços ou pedidos de outro cliente.
- [ ] Expiração automática permanece adiada.

Esta etapa define a estrutura; não cria models, migrations ou funcionalidades. A próxima etapa é verificar e completar a inicialização do Laravel existente, somente após o usuário dizer “próximo”.
