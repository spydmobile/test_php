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
   * @var \scripts\State[]
   */
  private array $states;

  /**
   * @var \scripts\Transition[]
   */
  private array $transitions;

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
    $this->states = array_map(fn($id, $definition) => State::fromDefinition($id, $definition), array_keys($workflow['states']), $workflow['states']);
    $this->transitions = array_map(fn($id, $definition) => Transition::fromDefinition($id, $definition), array_keys($workflow['transitions']), $workflow['transitions']);
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
    return array_map(fn ($id, $workflow) => new static($id, $workflow), array_keys($workflows), $workflows);
  }

  /**
   * Writes a mermaid graph of the workflow.
   *
   * See https://mermaid.ink/
   */
  public function print($prefix) {
    echo $prefix . "subgraph {$this->id}" . PHP_EOL;
    foreach ($this->states as $state) {
      echo $prefix . "\t" . $state->format($this->escaper) . PHP_EOL;
    }
    foreach ($this->transitions as $transition) {
      foreach ($transition->format($this->escaper) as $formattedTransition) {
        echo $prefix . "\t" . $formattedTransition . PHP_EOL;
      }
    }
    echo $prefix . "end" . PHP_EOL;
  }
}

final class State {
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

final class Transition {
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
