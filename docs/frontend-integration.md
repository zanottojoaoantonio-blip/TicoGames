# Integração do frontend

O frontend estático usa Axios para acessar o Laravel pelo proxy. Todas as páginas carregam config.js antes de store.js. Store centraliza sessão, carrinho, requests, validação de respostas, dinheiro e avisos; main.js atualiza navegação, minicarrinho e confirmações.

## Sessão e carrinho

`Store.initialize()` consulta GET /api/me. Somente 401 significa visitante; falha de rede/servidor é exibida como erro de sessão. Depois consulta GET /api/cart para visitante ou conta. O carrinho permanece no banco, sem soma ou importação de itens locais no navegador.

Login/cadastro preparam CSRF e autenticam. AuthService já funde o visitante antes de responder. `Store.acceptSession()` guarda a conta em memória e relê GET /api/cart; a primeira leitura também traz warnings. Uma falha nessa leitura permite tentar novamente sem repetir o login nem a fusão.

O token visitante só existe na sessão da API e seu hash no banco. O frontend não envia identificadores de sessão no JSON. Quantidade zero remove, DELETE limpa/remove e totais vêm do Service. Logout invalida a sessão e conserva o carrinho da conta.

## Escritas e falhas

`Store.request()` prepara GET /sanctum/csrf-cookie antes das escritas e envia cookies/CSRF. O bloqueio busy impede dois envios simultâneos pela mesma tela. Não repete escritas automaticamente após erro ou resposta perdida.

| HTTP | Apresentação |
| --- | --- |
| 401 | Sessão encerrada, orientação para entrar |
| 403 | Falta de permissão |
| 404 | Registro indisponível para a conta |
| 409 | Estoque ou transição em conflito |
| 419 | Sessão/CSRF expirado |
| 422 | Erros dos campos ou message, incluindo carrinho vazio |
| 429 | Aguardar antes de nova tentativa |

Erros mantêm formulários e reativam controles. Textos da API são inseridos como texto; preços decimais são convertidos a centavos com BigInt apenas para apresentação. A API decide preço, estoque, dono e total.

## Telas

Catálogo consulta opções da API, pesquisa/filtros/ordenação/paginação no servidor e guarda seleção na URL. Detalhes validam o ID antes da consulta e só permitem compra de produto disponível.

Administração exige admin, consulta opções reais, cria/edita/desativa produtos, gerencia categorias e envia imagens validadas. Perfil edita somente a conta atual.

Checkout consulta os próprios endereços e exige escolha explícita. Envia somente address_id. Sucesso cria pedido pendente, reserva estoque e esvazia o carrinho. Resposta perdida orienta consultar Pedidos antes de repetir.

Pedidos apresentam nomes/preços/endereço históricos e datas. Pendentes oferecem pagamento simulado e cancelamento com confirmação. Pago só oferece envio ao admin; enviado só oferece entrega. Nenhum perfil vê cancelamento de pedido pago.

## Navegação e verificação

As oito páginas mantêm Perfil/Pedidos no desktop e celular; admin vê “Pedidos”, cliente “Meus pedidos”. Páginas privadas encaminham visitantes ao login com continuação limitada a destinos locais conhecidos.

HTML e scripts do frontend são copiados para a imagem Docker; reconstrua para publicar mudanças em localhost. A API usa código montado. Veja [testes](frontend-verification.md) e [frontend](../front/README.md).
