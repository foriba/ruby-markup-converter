<?php
use Foriba\RubyMarkupConverter\Markup\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Bouten_Rendering_Method;
use Foriba\RubyMarkupConverter\Markup\Rule_Type;
foreach ([Bouten_Style::class => ['dot', 'sesame'], Bouten_Rendering_Method::class => ['text_emphasis', 'custom'], Rule_Type::class => ['ruby', 'bouten']] as $class => $values) {
    check_same($values, $class::values(), $class . ' values');
    foreach ($values as $i => $value) {
        check_same($value, $class::from($value)->get_value(), 'round trip');
        check_same($class::from($value), $class::cases()[$i], 'shared instance');
    }
    foreach (['', 'unknown', strtoupper($values[0]), ' ' . $values[0]] as $value) {
        check_same(null, $class::try_from($value), 'invalid value');
        $thrown = false;
        try { $class::from($value); } catch (InvalidArgumentException $e) { $thrown = true; }
        check_same(true, $thrown, 'invalid value throws');
    }
}
