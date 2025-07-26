<?php

return [

    'accepted' => 'Le champ :attribute doit être accepté.',
    'accepted_if' => 'Le champ :attribute doit être accepté lorsque :other vaut :value.',
    'active_url' => 'Le champ :attribute doit être une URL valide.',
    'after' => 'Le champ :attribute doit être une date postérieure au :date.',
    'after_or_equal' => 'Le champ :attribute doit être une date postérieure ou égale à :date.',
    'alpha' => 'Le champ :attribute ne peut contenir que des lettres.',
    'alpha_dash' => 'Le champ :attribute ne peut contenir que des lettres, chiffres, tirets et underscores.',
    'alpha_num' => 'Le champ :attribute ne peut contenir que des lettres et des chiffres.',
    'array' => 'Le champ :attribute doit être un tableau.',
    'ascii' => 'Le champ :attribute ne peut contenir que des caractères ASCII.',
    'before' => 'Le champ :attribute doit être une date antérieure à :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date antérieure ou égale à :date.',

    'between' => [
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'file' => 'Le champ :attribute doit être compris entre :min et :max kilo-octets.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caractères.',
        'array' => 'Le champ :attribute doit contenir entre :min et :max éléments.',
    ],

    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'date_equals' => 'Le champ :attribute doit être une date égale à :date.',
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'digits' => 'Le champ :attribute doit contenir :digits chiffres.',
    'digits_between' => 'Le champ :attribute doit contenir entre :min et :max chiffres.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'exists' => 'Le champ :attribute sélectionné est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'filled' => 'Le champ :attribute est requis.',
    'gt' => [
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
        'file' => 'Le champ :attribute doit être supérieur à :value kilo-octets.',
        'string' => 'Le champ :attribute doit contenir plus de :value caractères.',
        'array' => 'Le champ :attribute doit contenir plus de :value éléments.',
    ],
    'gte' => [
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :value.',
        'file' => 'Le champ :attribute doit être supérieur ou égal à :value kilo-octets.',
        'string' => 'Le champ :attribute doit contenir au moins :value caractères.',
        'array' => 'Le champ :attribute doit contenir :value éléments ou plus.',
    ],
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'Le champ :attribute sélectionné est invalide.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'ip' => 'Le champ :attribute doit être une adresse IP valide.',
    'json' => 'Le champ :attribute doit être une chaîne JSON valide.',
    'max' => [
        'numeric' => 'Le champ :attribute ne peut pas être supérieur à :max.',
        'file' => 'Le champ :attribute ne peut pas dépasser :max kilo-octets.',
        'string' => 'Le champ :attribute ne peut pas contenir plus de :max caractères.',
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
    ],
    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'file' => 'Le champ :attribute doit faire au moins :min kilo-octets.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
    ],
    'not_in' => 'Le champ :attribute sélectionné est invalide.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'same' => 'Les champs :attribute et :other doivent correspondre.',
    'size' => [
        'numeric' => 'Le champ :attribute doit être :size.',
        'file' => 'Le champ :attribute doit faire :size kilo-octets.',
        'string' => 'Le champ :attribute doit contenir :size caractères.',
        'array' => 'Le champ :attribute doit contenir :size éléments.',
    ],
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'timezone' => 'Le champ :attribute doit être un fuseau horaire valide.',
    'unique' => 'Le champ :attribute est déjà utilisé.',
    'url' => 'Le champ :attribute doit être une URL valide.',
    'uuid' => 'Le champ :attribute doit être un UUID valide.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'message personnalisé',
        ],
    ],

    'attributes' => [],

];
