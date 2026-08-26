<?php
namespace ScratchByPHP\Cloud;
final class CloudVariable {
    public function __construct(public readonly string $name, public readonly string $value, public readonly ?string $user = null) {}
}
