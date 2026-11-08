# Modelo de dados

A loja mantém catálogo, contas, endereços, carrinhos e pedidos. Preços e totais usam decimal(10,2), expostos como texto com duas casas. Estoque e quantidades são inteiros.

| Tabela | Responsabilidade |
| --- | --- |
| users | Nome, email único, senha com hash e role customer/admin |
| addresses | Endereços da conta; número/CEP são textos |
| categories | Classificação principal do produto |
| platforms | Plataforma da versão do produto |
| genres | Gêneros opcionais |
| products | Categoria e plataforma obrigatórias, nome, descrição, imagem, preço, estoque e atividade |
| genre_product | Relação de vários gêneros por produto |
| carts | Carrinho da conta ou visitante; user_id pode ser nulo |
| cart_items | Produto e quantidade; combinação carrinho/produto única |
| orders | Dono, estado, total, paid_at e cópia shipping_* do endereço |
| order_items | Produto e cópia histórica do nome, preço e quantidade |
| category_product | Arquivo da estrutura antiga, sem uso pela aplicação |

Cada produto pertence a uma categoria e plataforma. Gêneros são muitos-para-muitos. Um usuário tem vários endereços/pedidos e um carrinho. Visitantes têm carrinhos separados identificados pelo hash de um token aleatório da sessão.

## Histórico e estoque

Itens de carrinho usam o preço atual do produto. Itens de pedido preservam nome/preço no checkout; shipping_* preserva o endereço. A data de pagamento pode ser nula em pedidos ainda não pagos e em pagamentos antigos sem registro preciso.

O estoque diminui na criação do pedido, é mantido ao pagar e volta somente no cancelamento de pendentes. Estados: pending_payment, paid, shipped, delivered, cancelled. Pagos só avançam para enviados; enviados, para entregues. Admin não cancela pagos.

Produtos são desativados para manter referências históricas. Exclusão de classificações em uso ou contas com pedidos é impedida. As regras de dono são aplicadas pelos Services, além das FKs do banco.

## Evolução

A migration de evolução introduziu categoria única, visitante no carrinho e estados adicionais. Ela preserva dados antigos, verifica categoria única e reserva estoque de pendentes anteriores. Consulte [migrations](migrations.md) antes de atualizar uma base.

Resources mantêm `categories: [categoria]` por compatibilidade de leitura. Gravações atuais usam `category_id`, conforme [CRUD de produtos](crud-products.md).
