# Sessão e recuperação do carrinho

Store separa o carregamento da sessão e do carrinho. Assim, a conta pode aparecer no cabeçalho mesmo quando GET /api/cart falha. Uma falha de sessão diferente de 401 não transforma silenciosamente a pessoa em visitante.

## Leitura e recuperação

Store.session acompanha a leitura de GET /api/me; Store.ready inclui a leitura de GET /api/cart. cartLoaded, cartLoading e cartError permitem distinguir espera, sucesso e falha. A interface oferece “Tentar novamente” e encerra o indicador de carregamento em erro.

Antes de uma ação nova, ensureReady pode recuperar a leitura. Escritas não são repetidas automaticamente. Busy impede envios simultâneos pela mesma tela; os Services aplicam bloqueios transacionais no servidor.

## Fusão automática

Login/cadastro chamam CartService::merge no AuthService antes de concluir a autenticação. O carrinho visitante é somado, ajustado ao estoque e consumido. O token é removido da sessão e a primeira consulta ao carrinho entrega warnings.

Store.loadCart sempre usa GET /api/cart, inclusive para a conta autenticada. Não depende de POST /api/cart/merge para concluir um login. O endpoint explícito continua disponível por compatibilidade e repetir não duplica o visitante já consumido.

## Arquivos servidos

O carrinho agora carrega config.js e mantém links de Perfil/Pedidos nos menus desktop e móvel, como as demais páginas. A imagem do frontend precisa ser reconstruída após editar arquivos:

```powershell
docker compose up -d --build --no-deps --wait frontend
```

Os testes HTML verificam a ordem dos scripts e os links em todas as páginas; Store/main cobrem leitura, recuperação e nome da conta com falha de carrinho. Consulte [verificação](frontend-verification.md).
