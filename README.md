# Monitoramento de métricas do YouTube

Projeto acadêmico de **Laís e Thaissa**, desenvolvido para a disciplina de Processos Decisórios e Sistemas de Apoio à Decisão. A proposta é acompanhar métricas de um vídeo e explorar como dados digitais podem apoiar a análise de tendências e engajamento.

## Como funciona

1. `scraper.php` solicita a página do vídeo por meio da ScraperAPI.
2. O script extrai visualizações, curtidas, comentários, título e canal.
3. Cada coleta é adicionada ao histórico em `dados.json`.
4. O GitHub Actions automatiza a coleta, e `index.php` consulta o JSON público do GitHub para montar o painel.

## O que o painel mostra

- Métricas da última coleta e histórico em gráficos.
- Crescimento das visualizações e relação entre curtidas e visualizações.
- Variações entre coletas e indicadores exploratórios de tendência.
- Projeção simples com regressão linear.

## Tecnologias

PHP, HTML, CSS, Chart.js, JSON, ScraperAPI e GitHub Actions.

## Como visualizar localmente

Com PHP instalado e conexão com a internet, abra um terminal na pasta do projeto e execute:

```bash
php -S localhost:8000
```

Acesse `http://localhost:8000`. O painel consulta o histórico publicado no GitHub; ele não lê automaticamente uma cópia local de `dados.json`. Para usar outra fonte, ajuste a URL em `index.php`.

A execução do coletor requer uma credencial válida da ScraperAPI, acesso externo habilitado no PHP e permissão para gravar `dados.json`. Para reutilizar ou publicar o projeto, configure sua própria credencial fora do código versionado e ajuste a integração antes de executar.

## Estrutura

| Arquivo | Finalidade |
| --- | --- |
| `scraper.php` | Coleta e armazenamento do histórico |
| `dados.json` | Registros das coletas |
| `index.php` | Painel e cálculos dos indicadores |
| `style.css` | Apresentação visual |
| `.github/workflows/` | Automação pelo GitHub Actions |

## Limitações

- A extração depende do conteúdo retornado pelo YouTube e pela ScraperAPI. Mudanças na página podem exigir ajustes.
- Campos não encontrados podem ser registrados como zero; esse valor também pode representar falha de extração.
- Os rótulos de tendência e viralização são heurísticas acadêmicas, sem validação preditiva.
- As variações são calculadas por intervalo de coleta. A interpretação em horas e da projeção de 24 horas depende de coletas igualmente espaçadas a cada hora.
- Muitos commits do histórico são gerados automaticamente pela atualização dos dados.

## Autoria

Laís e Thaissa. Perfil de [Laís Moscon](https://github.com/laismoscon).
