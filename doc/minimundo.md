# Entidades

- Pessoa
- Função
- Vínculo
- Local de coleta
- Coleta (Superclasse)
- Coleta Residencial (Herda de Coleta)
- Coleta Presencial (Herda de Coleta)
- Tipo de Material
- Tipo de Premio
- Reivindicação de prêmio


# Atributos por Classe

- ## Pessoa
- `ID -> UUID`
- `Email -> Email`
- `Email Verificado -> Bool`
- `Hash de Senha -> String`
- `Número de Telefone -> Phone`
- `CPF -> CPF`
- `Nome -> String`
- `Data de Nascimento -> Date`
- `Quantidade de Pontos Agora -> Int`
- `Ativo -> Bool`

- ## Vínculo
- `ID -> UUID`
- `ID da Pessoa (FK Pessoa) -> UUID`
- `ID da Função (FK Função) -> Int`
- `Respondido Por (FK Vínculo) -> UUID`
- `Encerrado Por (FK Vínculo) -> UUID`
- `Solicitado Em -> Datetime`
- `Respondido Em -> Datetime`
- `Status -> String`
- `Justificativa da Resposta -> String`
- `Justificativa da Demissão -> String`
- `Encerrado Em -> Datetime`

- ## Coleta
- `ID -> String`
- `Coletado Por (FK Vinculo) -> UUID`
- `ID do Tipo de Material (FK Tipo de Material)-> Int`
- `Coletado Em -> Datetime`
- `Tipo de coleta -> String`
- `Quantidade -> Float`
- `Observação -> String`
- `Ativo -> Bool`

- ## Coleta Presencial
*(Herda todos os atributos da classe Coleta)*

- ## Coleta Residencial
*(Herda os atributos da classe Coleta e adiciona os seguintes)*
- `ID do Local de Coleta (FK Local de Coleta) -> UUID`
- `Solicitado Em -> Datetime`
- `Data de Desativação -> Datetime`
- `Justificativa de Desativação -> String`
- `Descrição -> String`
- `Status -> String`

- ## Função
- `ID -> Int`
- `Nome -> String`
- `Active -> Bool`

- ## Local de Coleta
- `ID -> UUID`
- `Email do Responsável -> Email`
- `Email Verificado -> Bool`
- `Hash de Senha -> String`
- `Telefone do Responsável -> Phone`
- `Rua -> String`
- `Número -> String`
- `Bairro -> String`
- `Complemento -> String`
- `Cidade -> String`
- `Estado -> String`
- `CEP -> Cep`
- `Quantidade de Pontos Agora -> Int`
- `Ativo -> Bool`

- ## Reivindicação de Prêmio
- `ID -> Int`
- `ID do Tipo de Prêmio (FK Tipo de Prêmio) -> Int`
- `Coletador Por -> UUID`
- `Status -> String`
- `Solicitado Em -> Datetime`
- `Realizado Em -> Datetime`

- ## Tipo de Material
- `ID -> Int`
- `Nome -> String`
- `Preço por Peso -> Float`
- `Preço por Unidade-> Float`
- `Pontos por Peso -> Int`
- `Pontos por Unidade-> Int`

- ## Tipo de Prêmio
- `ID -> Int`
- `Nome -> String`
- `Descrição -> String`
- `Custo em Pontos -> Int`
- `Ativo -> Bool`



### Inicialização
1. O sistema já possui um administrador cadastrado por padrão.
2. Os tipos de materiais iniciais são madeira, alumínio e plástico PET

### Vínculo (Pessoa ↔ Função)
3. Uma pessoa pode não ter vínculo algum; para realizar qualquer atividade operacional é obrigatório possuir um vínculo aprovado e com `encerrado em` nula (vínculo ativo).
4. Cada vínculo associa uma pessoa a uma única função. Além disso, uma Pessoa pode possuir de zero a muitos Vínculos ao longo do tempo (Pessoa 1:N Vínculo), e uma Função pode estar associada a de zero a muitos Vínculos (Função 1:N Vínculo).
5. O campo `respondido por` permanece nulo enquanto o vínculo estiver com status "pendente". Quando um administrador aprova ou recusa, esse campo é preenchido com o seu identificador.
6. Um administrador pode responder a zero ou vários vínculos pendentes; cada vínculo é respondido por no máximo um administrador.
7. `respondido em` é registrada apenas quando o status deixa de ser "pendente". Se o vínculo for aprovado, a pessoa torna-se cooperada a partir desse momento. Se a solicitação de vínculo for cancelada, a data de resposta deverá ser registrada com a data de cancelamento.
8. `encerrado em` só é preenchida no encerramento do vínculo (desligamento/demissão). Enquanto essa data for nula e o status for "aprovado", o vínculo está ativo.
9. Uma pessoa pode ter vários vínculos ao longo do tempo para funções diferentes.
10. Não é permitido que a mesma pessoa possua dois vínculos com status "pendente" simultaneamente para a mesma função.
11. Não é permitido criar um novo vínculo para uma função se a pessoa já possui um vínculo aprovado com `encerrado em` nula (vínculo ativo) para essa mesma função.
12. Após um vínculo ter sido recusado para uma determinada função, um novo pedido para a mesma função só poderá ser criado depois de três meses, contados a partir de `respondido em` da recusa.

### Coleta (Geral)
13. O tipo de coleta deve ser por unidade ou por pesagem.

### Coleta Residencial
14. Um local de coleta pode ter de zero a várias coletas residenciais associadas (Local de Coleta 1:N Coleta Residencial).
15. Cada coleta residencial pertence a exatamente um local de coleta, e possui exatamente um tipo de material (Tipo de Material 1:N Coleta) e, quando concluída, a um único vínculo (Vínculo 1:N Coleta).
16. Enquanto o status for `pendente`, os campos `coletado por` e `coletado em` permanecem nulos. Quando a coleta é efetivada (status `concluída`), ambos os campos devem ser preenchidos, e o campo `quantidade` e `tipo de coleta` torna-se obrigatório.
17. Uma coleta com status `cancelado` não pode ser descancelada (não é possível alterar seu status para `pendente` ou `concluída`). Se necessário, deve-se criar uma nova coleta residencial.
18. Caso cancelado, a data de cancelamento deve ser registrada em data de realização.
19. O usuário define informações na `descrição` enquanto os dados de fato devem ser definidos pelo cooperado.
20. Uma coleta residencial só poderá ter dados alterados enquanto estiver com `status` igual a `pendente`.

### Coleta Presencial
19. A coleta presencial é um registro de material efetivamente recebido na cooperativa. Ela ocorre no ato e não é cancelável.
20. Cada coleta presencial está associada a exatamente um vínculo ativo (Vínculo 1:N Coleta) e a exatamente um tipo de material (Tipo de Material 1:N Coleta).
21. O campo `quantidade` e `tipo de coleta` é sempre obrigatório.
22. Um cooperado pode realizar várias entregas presenciais ao longo do tempo.

### Materiais, valores e pontuação
23. Um tipo de material pode estar presente em muitas coletas, sendo que cada coleta está associada a exatamente um tipo de material (Tipo de Material 1:N Coleta).
24. Na coleta, o valor financeiro pode ser calculado com base no tipo de coleta, quantidade e o valor respectivo por quantia.

### Tipo de Prêmio
25. Um tipo de prêmio define um item ou benefício que pode ser resgatado em troca de pontos acumulados.
26. O campo `custo em pontos` indica quantos pontos são debitados do saldo para realizar a reivindicação.
27. Apenas prêmios com o atributo `ativo` igual a `true` podem ser selecionados para novas reivindicações.

### Reivindicação de Prêmio
28. Uma reivindicação de prêmio está associada a exatamente um Tipo de Prêmio (Tipo de Prêmio 1:N Reivindicação de Prêmio) e a exatamente um Local de Coleta (Local de Coleta 1:N Reivindicação de Prêmio).
29. Ao criar uma nova reivindicação, o status inicial deve ser definido como "pendente" e a `solicitado em` deve ser preenchida com a data e hora atuais.
30. O sistema deve validar se o saldo disponível em `Quantidade de Pontos Agora` do local de coleta é maior ou igual ao `custo em pontos` do prêmio desejado antes de permitir a solicitação efetuando o respectivo desconto dos pontos do saldo. Se o saldo for insuficiente, a reivindicação deve ser rejeitada/impedida.
31. Quando a reivindicação for atendida/entregue, o status deve ser atualizado para "concluído" (ou "entregue") e o campo `realizado em` deve ser preenchido.
32. Uma reivindicação com status "cancelado" não pode ter seu status revertido para "pendente" ou "concluído", caso necessário, deve-se criar uma nova reivindicação.
