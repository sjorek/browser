<?php

/*
 * This file is part of the sjorek/browser package.
 *
 * © Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!file_exists(__DIR__ . '/src')) {
    exit(0);
}

$fileHeaderComment = <<<'EOF'
    This file is part of the sjorek/browser package.

    © Stephan Jorek <stephan.jorek@gmail.com>

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    EOF;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PHP82Migration' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'protected_to_private' => false,
        'native_constant_invocation' => ['strict' => false],
        'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => false],
        'no_superfluous_phpdoc_tags' => ['remove_inheritdoc' => true],
        'header_comment' => ['header' => $fileHeaderComment],
        'modernize_strpos' => true,
        'get_class_to_class_keyword' => true,
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder((new PhpCsFixer\Finder())
        ->in([__DIR__ . '/src', __DIR__ . '/scripts'])
        ->append([__FILE__, __DIR__ . '/bin/browser'])
        ->notPath('#/Fixtures/#')
        // ->exclude([
        //     '…'
        // ])
        // ->notPath('…')
    )
    ->setCacheFile('.php-cs-fixer.cache')
;
