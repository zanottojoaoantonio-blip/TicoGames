# CRUD de produtos

ProductController recebe dados validados; ProductService concentra consultas, transações e vínculos; ProductResource define a saída.

## Entrada

| Campo | Regra principal |
| --- | --- |
| name | Obrigatório, texto até 255 |
| description | Obrigatório, texto até 10000 |
| image_path | Obrigatório, caminho/URL até 255 |
| platform_id | Plataforma existente |
| category_id | Uma categoria existente |
| price | 0 a 99999999.99, até duas casas decimais |
| stock | Inteiro de 0 a 4294967295 |
| is_active | Booleano opcional |
| genre_ids | Lista opcional de IDs existentes, sem repetição |

No PATCH, campos omitidos são preservados. Enviar genre_ids=[] remove os gêneros; omitir preserva. Categoria não é uma lista: use category_id.

## Exemplo

Depois de consultar os IDs reais e autenticar como admin:

```json
{
  "name": "Produto de exemplo",
  "description": "Exemplo fictício",
  "image_path": "/images/product-placeholder.svg",
  "platform_id": 1,
  "category_id": 1,
  "price": "149.90",
  "stock": 5,
  "is_active": true,
  "genre_ids": []
}
```

POST /api/products cria e retorna 201. PATCH /api/products/{id} atualiza e retorna 200. As escritas de produto e gêneros são transacionais.

GET /api/products usa filtros/ordenação/paginação no servidor; GET por ID carrega categoria/plataforma/gêneros. Visitantes/clientes recebem somente ativos; admin também recebe inativos. Respostas usam preço textual, estoque inteiro, category_id/category e categories=[categoria] por compatibilidade.

DELETE /api/products/{id} desativa e retorna 204, sem apagar histórico. Reativação usa PATCH com is_active=true. Produto inexistente retorna 404; entrada inválida retorna 422.

## Imagens

POST /api/product-images exige admin e multipart/form-data com image JPEG/PNG/WebP de até 5 MB. Retorna image_path para cadastrar no produto. Arquivos recebem nomes aleatórios no volume api_uploads e são servidos por /storage/products/{arquivo}. URLs externas continuam aceitas.

Testes: ProductApiTest e CatalogWorkflowTest. Consulte [filtros](catalog-filters.md), [validação](requests-resources.md) e [autenticação](authentication.md).
