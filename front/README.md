# Frontend TicoGames

O projeto usa Vue 3 e Vite. O Docker compila o frontend com `npm ci` e `npm run build`, e o Nginx serve o resultado.

Na raiz do repositório, execute `docker compose up -d --build --wait` e abra [http://localhost](http://localhost). Use URLs relativas para a API, como `/api/products` e `/sanctum/csrf-cookie`; o proxy encaminha essas chamadas ao Laravel. Veja o [guia do Sanctum](../docs/authentication.md#testar-no-navegador).

Depois de alterar o Vue, execute `docker compose up -d --build --wait frontend` na raiz. Para editar apenas a interface com recarga automática, rode `npm ci` e `npm run dev` nesta pasta; para verificar a sessão do Sanctum, use o endereço do proxy.
