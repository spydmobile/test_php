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
   * @var \scripts\Step[]
   */
  private array $steps;



  /**
   * @var callable
   */
  private $escaper;

  public function __construct($id, $workflow) {
    var_dump($workflow);
    $this->id = $id;
    $this->steps = array_map(fn($id, $definition) => Step::fromDefinition($id, $definition), array_keys($workflow['steps']), $workflow['steps']);
//     $this->uses = array_map(fn($id, $definition) => Stepuse::fromDefinition($id, $definition), array_keys($workflow['uses']), $workflow['uses']);
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
     var_dump($workflows['jobs']['build']);
    return array_map(fn ($id, $workflow) => new static($id, $workflow), array_keys($workflows['jobs']['build']), $workflows['jobs']['build']);
  }

  /**
   * Writes a mermaid graph of the workflow.
   *
   * See https://mermaid.ink/
   */
  public function print($prefix) {
    echo $prefix . "subgraph {$this->id}" . PHP_EOL;
    foreach ($this->steps as $step) {
      echo $prefix . "\t" . $step.name->format($this->escaper) . PHP_EOL;
    }
//     foreach ($this->uses as $use) {
//       foreach ($use->format($this->escaper) as $formattedUse) {
//         echo $prefix . "\t" . $formattedUse . PHP_EOL;
//       }
//     }
    echo $prefix . "end" . PHP_EOL;
  }
}

final class Step {
  public string $id;
  public string $name;

  public function __construct($id, $name, $from, $to) {
    $this->id = $id;
    $this->name = $name;
    $this->from = $from;
    $this->to = $to;
  }

  public static function fromDefinition($id, $definition): self {
    return new static($id, $definition['name']);
  }

  public function format(callable $escaper): string {
    return "{$this->id}[{$escaper($this->name)}]";
  }



  public static function fromDefinition($id, $definition): self {
    return new static($id, $definition['uses'], $definition['name'], $definition['with']);
  }

  /**
   * @return string[]
   */
  public function format(callable $escaper): array {
    return array_map(fn ($from) => "{$from} --> |{$escaper($this->label)}| {$this->to}", $this->from);
  }
}
