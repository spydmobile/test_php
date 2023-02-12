<?php

namespace scripts;

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php';

$workflowFile = $argv[1];

echo "flowchart TB" . PHP_EOL;
foreach (WorkflowPrinter::loadFromFile($workflowFile) as $workflow) {
  $workflow->print("\t");
}

final class WorkflowPrinter {

  private string $id;

  /**
   * @var \scripts\Name[]
   */
  private array $names;

  /**
   * @var \scripts\Stepuse[]
   */
  private array $uses;

  /**
   * @var callable
   */
  private $escaper;

  public function __construct($id, $workflow) {
//     if (!is_array($workflow)) {
//     throw new \Exception("Unexpected type: expected array, got " . gettype($workflow));
//     }
    var_dump($workflow);
    $this->id = $id;
    $this->names = array_map(fn($id, $definition) => Name::fromDefinition($id, $definition), array_keys($workflow['name']), $workflow['name']);
    $this->uses = array_map(fn($id, $definition) => Stepuse::fromDefinition($id, $definition), array_keys($workflow['uses']), $workflow['uses']);
    $this->escaper = fn ($x) => "\"{$x}\"";
  }

  /**
   * @return \scripts\WorkflowPrinter[]
   */
  public static function loadFromFile(string $path): array {
    $workflows = Yaml::parse(file_get_contents($path));
    if (!is_array($workflows)) {
      throw new \Exception("Unexpected return value from WorkflowPrinter::loadFromFile: expected array, got " . gettype($workflows));
    }    
     var_dump($workflows['jobs']['build']['steps']);
    return array_map(fn ($id, $workflow) => new static($id, $workflow), array_keys($workflows['jobs']['build']['steps']), $workflows['jobs']['build']['steps']);
  }

  /**
   * Writes a mermaid graph of the workflow.
   *
   * See https://mermaid.ink/
   */
  public function print($prefix) {
    echo $prefix . "subgraph {$this->id}" . PHP_EOL;
    foreach ($this->names as $name) {
      echo $prefix . "\t" . $name->format($this->escaper) . PHP_EOL;
    }
    foreach ($this->uses as $use) {
      foreach ($use->format($this->escaper) as $formattedUse) {
        echo $prefix . "\t" . $formattedUse . PHP_EOL;
      }
    }
    echo $prefix . "end" . PHP_EOL;
  }
}

final class Name {
  public string $id;
  public string $label;

  public function __construct($id, $label) {
    $this->id = $id;
    $this->label = $label;
  }

  public static function fromDefinition($id, $definition): self {
    return new static($id, $definition['label']);
  }

  public function format(callable $escaper): string {
    return "{$this->id}[{$escaper($this->label)}]";
  }

}

final class Stepuse {
  public string $id;
  public string $label;

  /**
   * @var string[]
   */
  public array $from;
  public string $to;

  public function __construct($id, $label, $from, $to) {
    $this->id = $id;
    $this->label = $label;
    $this->from = $from;
    $this->to = $to;
  }

  public static function fromDefinition($id, $definition): self {
    return new static($id, $definition['label'], $definition['from'], $definition['to']);
  }

  /**
   * @return string[]
   */
  public function format(callable $escaper): array {
    return array_map(fn ($from) => "{$from} --> |{$escaper($this->label)}| {$this->to}", $this->from);
  }
}
