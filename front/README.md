# Frontend TicoGames

O frontend usa HTML, CSS, JavaScript puro e [Bootstrap 5.3.8](https://getbootstrap.com/docs/5.3/getting-started/introduction/). O Nginx serve os arquivos diretamente, sem Node, npm ou compilação.

## Arquivos

```text
front/
├── index.html              # Catálogo com sete produtos ilustrativos
├── produto.html            # Visual de detalhes de um produto
├── carrinho.html           # Visual do carrinho com itens fixos
├── login.html              # Visual de acesso e cadastro
├── admin.html              # Tabela e formulário visual de produtos
├── assets/
│   ├── css/style.css        # Estilos compartilhados e responsivos
│   ├── js/main.js           # Modais de demonstração e rolagem até o formulário
│   └── images/              # Ilustrações locais de jogos e consoles
├── Dockerfile              # Copia os arquivos para o Nginx
└── nginx.conf              # Configura o servidor estático
```

O Bootstrap está configurado pelo CDN indicado na documentação, com versão fixa e integridade SRI. Bootstrap Icons e a fonte Inter também usam serviços externos e precisam de internet. As cinco páginas compartilham o CSS, com cabeçalho azul, destaques laranja e identidade TicoGames.

## Executar

Com o ambiente preparado conforme [docs/README.md](../docs/README.md), execute na raiz do repositório:

```bash
docker compose up -d --build --wait
```

Abra [http://localhost](http://localhost). Depois de editar HTML, CSS ou JavaScript, atualize a imagem do frontend:

```bash
docker compose up -d --build --no-deps --wait frontend
```

## Telas e limites da prévia

- [Catálogo](http://localhost/): sete exemplos de jogos e consoles, com cards em quatro colunas no desktop, duas no tablet e uma no celular. A pesquisa é apenas visual.
- [Produto](http://localhost/produto.html): exemplo estático com galeria ilustrativa, preço e abas de descrição e especificações.
- [Carrinho](http://localhost/carrinho.html): itens e totais fixos para revisar o layout; os campos não recalculam valores.
- [Acesso](http://localhost/login.html): abas de entrar e criar conta, sem autenticação nem armazenamento de credenciais.
- [Administração](http://localhost/admin.html): tabela dos sete exemplos e formulário sempre visível com os campos previstos no backend. A tabela tem rolagem horizontal própria em telas estreitas.
- Comprar, desativar e salvar abrem um modal de demonstração. Confirmar apenas fecha esse modal. Editar e Novo produto levam até o formulário, sem carregar dados do produto.

O JavaScript é usado somente para apresentação. Não há chamadas à API, login funcional, operações de carrinho, pedidos, CRUD, upload nem persistência. Alterar os campos não modifica os produtos. Os arquivos `data.js`, `cart.js` e `auth.js` iniciados para a proposta anterior foram removidos a pedido do usuário; `assets/js/main.js` continua apenas com os avisos visuais e a rolagem até o formulário.

O Dockerfile copia as páginas HTML e os assets para o Nginx. A reconstrução isolada do frontend não executa migrations, seeders nem comandos no banco.

A versão simplificada não tem faixa promocional superior, banners, faixa de benefícios, área de ofertas, departamentos de hardware/periféricos/monitores/notebooks/celulares ou redes sociais no rodapé.

## API e autenticação

Quando a integração for autorizada, use URLs relativas como `/api/products` e `/sanctum/csrf-cookie`. O proxy já encaminha esses caminhos ao Laravel pela mesma origem, preservando os cookies do Sanctum. O fluxo futuro com `fetch`, CSRF e sessão está em [docs/authentication.md](../docs/authentication.md#testar-no-navegador).

Abra a página pelo proxy para testar a API; abrir `index.html` diretamente como arquivo não reproduz a origem e os cookies do ambiente Docker. Caminhos ou arquivos inexistentes respondem 404, pois o frontend agora usa páginas HTML, sem o fallback de uma SPA.
