<?php

/*
 * Mensagens de validação em português (APP_LOCALE=pt_BR). Só as regras que o sistema usa;
 * ao usar uma regra nova, acrescente a mensagem aqui. Nomes amigáveis dos campos ficam em
 * attributes() de cada Form Request (app/Http/Requests).
 */
return [
    'after' => 'O campo :attribute precisa ser depois de :date.',
    'after_or_equal' => 'O campo :attribute precisa ser igual ou depois de :date.',
    'before' => 'O campo :attribute precisa ser antes de :date.',
    'boolean' => 'O campo :attribute precisa ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'date' => 'O campo :attribute não é uma data válida.',
    'email' => 'O campo :attribute precisa ser um e-mail válido.',
    'enum' => 'O :attribute selecionado é inválido.',
    'exists' => 'O :attribute informado não existe.',
    'file' => 'O campo :attribute precisa ser um arquivo.',
    'in' => 'O :attribute selecionado é inválido.',
    'integer' => 'O campo :attribute precisa ser um número inteiro.',
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
        'file' => 'O arquivo :attribute não pode ter mais de :max kilobytes.',
    ],
    'mimes' => 'O arquivo :attribute precisa ser do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute precisa ser no mínimo :min.',
        'string' => 'O campo :attribute precisa ter no mínimo :min caracteres.',
    ],
    'numeric' => 'O campo :attribute precisa ser um número.',
    'password' => [
        'letters' => 'A :attribute precisa ter pelo menos uma letra.',
        'mixed' => 'A :attribute precisa ter letras maiúsculas e minúsculas.',
        'numbers' => 'A :attribute precisa ter pelo menos um número.',
        'symbols' => 'A :attribute precisa ter pelo menos um símbolo.',
        'uncompromised' => 'Esta :attribute apareceu em um vazamento de dados. Escolha outra.',
    ],
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute precisa ser um texto.',
    'unique' => 'Este :attribute já está em uso.',

    'attributes' => [],
];
