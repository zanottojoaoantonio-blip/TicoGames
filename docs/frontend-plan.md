# Cobertura atual do frontend

Este roteiro descreve o que está implementado em 08/11/2026. Os comportamentos completos estão no [README](../README.md) e na [integração](frontend-integration.md).

| Fluxo | Implementação |
| --- | --- |
| Catálogo | API, pesquisa, categoria dinâmica, plataforma, gênero, ordenação e paginação |
| Detalhes | ID na URL, resposta validada, imagem alternativa e compra disponível |
| Acesso | Login/cadastro com sessão e CSRF; fusão no AuthService |
| Cabeçalho | Conta conectada, logout, Perfil/Pedidos e navegação por perfil |
| Carrinho | Persistido no servidor para visitante e conta; quantidade zero remove |
| Checkout | Endereços próprios, resumo, frete gratuito e criação com reserva |
| Pedidos | Histórico e datas; pagar/cancelar pendentes; envio/entrega administrativos |
| Perfil | Nome, email e senha da própria conta |
| Administração | Produtos, categorias, imagens e pedidos globais |
| Configuração | API_BASE_URL e config.js antes do Store em todas as oito páginas |
| Falhas | Mensagens, recuperação de leituras, validação e bloqueio de envios duplicados |

Os itens 1 a 5 da revisão foram tratados no backend, frontend, testes, documentação, Swagger e ambiente local. A [verificação](frontend-verification.md) registra os resultados.

Pagamento real, reembolso e expiração automática não fazem parte da implementação. Commit, envio ao GitHub e hospedagem foram adiados por orientação do usuário.
