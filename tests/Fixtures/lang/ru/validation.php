<?php

return [
    'required' => 'Поле :attribute обязательно.',
    'email' => 'Поле :attribute должно быть действительным email адресом.',
    'min' => [
        'string' => 'Поле :attribute должно содержать минимум :min символов.',
        'numeric' => 'Поле :attribute должно быть не менее :min.',
    ],
    'attributes' => [
        'name' => 'имя',
        'email' => 'email адрес',
    ],
    'plural_example' => [
        'one' => 'Один элемент',
        'other' => ':count элементов',
    ],
];
