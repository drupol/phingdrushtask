<?php

/**
 * @file
 * A Phing task to run Drush commands.
 */

require_once "phing/Task.php";

/**
 * A Drush CLI parameter.
 */
class DrushParam extends DataType {

  /**
   * The parameter's value.
   *
   * @var string
   */
  protected $value;

  /**
   * If TRUE, escape the value. Otherwise not. Default is TRUE.
   *
   * @var bool
   */
  protected $escape = TRUE;

  /**
   * If TRUE, surround the value with quotes. Otherwise not. Default is TRUE.
   *
   * @var bool
   */
  protected $quote = TRUE;

  /**
   * Set the escape's param value.
   *
   * @param bool $escape
   *   The escape's param value.
   */
  public function setEscape($escape = TRUE) {
    $this->escape = $escape;
  }

  /**
   * Get the escape's param value.
   *
   * @return bool
   *   The escape's param value.
   */
  public function getEscape() {
    return $this->escape;
  }

  /**
   * Set the quote's param value.
   *
   * @param bool $quote
   *   The quote's param value.
   */
  public function setQuote($quote = TRUE) {
    $this->quote = $quote;
  }

  /**
   * Get the quote's param value.
   *
   * @return bool
   *   The quote's param value.
   */
  public function getQuote() {
    return $this->quote;
  }

  /**
   * Set the parameter value from a text element.
   *
   * @param string $str
   *   The value of the text element.
   */
  public function addText($str) {
    $this->value = (string) $str;
  }

  /**
   * Get the parameter's value.
   *
   * @return string
   *   The parameter value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Get the string.
   *
   * @return string
   *   The parameter string.
   */
  public function toString() {
    $value = $this->getValue();

    if ($this->getEscape()) {
      $value = escapeshellcmd($value);
    }

    if ($this->getQuote()) {
      $value = '"' . $value . '"';
    }

    return $value;
  }

}

/**
 * A Drush CLI option.
 */
class DrushOption {

  /**
   * The option's name.
   *
   * @var string
   *   The option's name.
   */
  protected $name;

  /**
   * The option's value.
   *
   * @var string
   *   The option's value.
   */
  protected $value;

  /**
   * Set the option's name.
   *
   * @param string $str
   *   The option's name.
   */
  public function setName($str) {
    $this->name = (string) $str;
  }

  /**
   * Get the option's name.
   *
   * @return string
   *   The option's name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set the option's value.
   *
   * @param string $str
   *   The option's value.
   */
  public function setValue($str) {
    $this->value = $str;
  }

  /**
   * Set the option's value from a text element.
   *
   * @param string $str
   *   The value of the text element.
   */
  public function addText($str) {
    $this->value = (string) $str;
  }

  /**
   * Get the option's value.
   *
   * @return string
   *   The option's value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Get the a representation of the option.
   *
   * @return string
   *   The option as a string.
   */
  public function toString() {
    $name = $this->getName();
    $value = $this->getValue();
    $str = sprintf('--%s', $name);

    if (isset($value) && $value != '') {
      $str = sprintf('%s="%s"', $str, escapeshellcmd($value));
    }

    return $str;
  }

}

/**
 * DrushTask.
 *
 * Runs the Drush commad line tool.
 * See https://github.com/drush-ops/drush.
 */
class DrushTask extends Task {

  /**
   * The command to execute.
   *
   * @var string
   */
  protected $command = NULL;

  /**
   * Path the the Drush binary.
   *
   * @var PhingFile
   */
  protected $bin = 'drush';

  /**
   * URI of the Drupal site to use.
   *
   * @var PhingFile
   */
  protected $uri = NULL;

  /**
   * Drupal root directory to use.
   *
   * @var PhingFile
   */
  protected $root = NULL;

  /**
   * If set, assume 'yes' or 'no' as answer to all prompts.
   *
   * @var bool
   */
  protected $assume = FALSE;

  /**
   * If true, simulate all relevant actions.
   *
   * @var bool
   */
  protected $simulate = FALSE;

  /**
   * Use the pipe option.
   *
   * @var bool
   */
  protected $pipe = FALSE;

  /**
   * The 'glue' characters used between each line of the returned output.
   *
   * @var string
   */
  protected $returnGlue = "\n";

  /**
   * The name of a Phing property to assign the Drush command's output to.
   *
   * @var string
   */
  protected $returnProperty = NULL;

  /**
   * Display extra information about the command.
   *
   * @var bool
   */
  protected $verbose = FALSE;

  /**
   * Should the build fail on Drush errors.
   *
   * @var bool
   */
  protected $haltOnError = TRUE;

  /**
   * The alias of the Drupal site to use.
   *
   * @var string
   */
  protected $alias = NULL;

  /**
   * Path to an additional config file to load.
   *
   * @var string
   */
  protected $config = NULL;

  /**
   * Specifies the list of paths where drush will search for alias files.
   *
   * @var string
   */
  protected $aliasPath = NULL;

  /**
   * Whether or not to use colored output.
   *
   * @var bool
   */
  protected $color = FALSE;

  /**
   * Working directory.
   *
   * @var string
   */
  protected $dir;

  /**
   * An array of Option.
   *
   * @var DrushOption[]
   */
  protected $options = array();

  /**
   * An array of Param.
   *
   * @var DrushParam[]
   */
  protected $params = array();

  /**
   * The Drush command to run.
   *
   * @param string $command
   *   The Drush command's name.
   */
  public function setCommand($command) {
    $this->command = $command;
  }

  /**
   * Path the Drush executable.
   *
   * @param PhingFile $bin
   *   The path to the Drush executable.
   */
  public function setBin(PhingFile $bin) {
    $this->bin = $bin;
  }

  /**
   * Drupal root directory to use.
   *
   * @param PhingFile $root
   *   The Drupal's root directory to use.
   */
  public function setRoot(PhingFile $root) {
    $this->root = $root;
  }

  /**
   * URI of the Drupal to use.
   *
   * @param string $uri
   *   The URI of the Drupal site to use.
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Set the assume option. 'yes' or 'no' to all prompts.
   *
   * @param string $assume
   *   The assume option.
   */
  public function setAssume($assume) {
    $this->assume = $assume;
  }

  /**
   * Set the simulate option.
   *
   * @param string $simulate
   *   The simulate option.
   */
  public function setSimulate($simulate) {
    $this->simulate = $simulate;
  }

  /**
   * Set the the pipe option.
   *
   * @param bool $pipe
   *   The pipe option.
   */
  public function setPipe($pipe) {
    $this->pipe = $pipe;
  }

  /**
   * The 'glue' characters used between each line of the returned output.
   *
   * @param string $glue
   *   The glue character.
   */
  public function setReturnGlue($glue) {
    $this->returnGlue = (string) $glue;
  }

  /**
   * The name of a Phing property to assign the Drush command's output to.
   *
   * @param string $property
   *   The property's name.
   */
  public function setReturnProperty($property) {
    $this->returnProperty = $property;
  }

  /**
   * Should the task fail on Drush error (non zero exit code).
   *
   * @param string $haltOnError
   *   The value of the Halt On Error option.
   */
  public function setHaltOnError($haltOnError) {
    $this->haltOnError = $haltOnError;
  }
  /**
   * Display extra information about the command.
   *
   * @param bool $verbose
   *   The verbose option.
   */
  public function setVerbose($verbose) {
    $this->verbose = $verbose;
  }

  /**
   * Set the site alias.
   *
   * @param string $alias
   *   The site alias.
   */
  public function setAlias($alias) {
    $this->alias = $alias;
  }

  /**
   * Set the path to an additional config file to load.
   *
   * @param PhingFile $config
   *   The path to the additional config file to load.
   */
  public function setConfig(PhingFile $config) {
    $this->config = $config;
  }

  /**
   * Set the list of paths where drush will search for alias files.
   *
   * @param string $aliasPath
   *   The list of paths.
   */
  public function setAliasPath($aliasPath) {
    $this->aliasPath = $aliasPath;
  }

  /**
   * Whether or not to use color output.
   *
   * @param bool $color
   *   The color option.
   */
  public function setColor($color) {
    $this->color = $color;
  }

  /**
   * Specify the working directory for executing this command.
   *
   * @param PhingFile $dir
   *   Working directory.
   */
  public function setDir(PhingFile $dir) {
    $this->dir = $dir;
  }

  /**
   * {@inheritdoc}
   */
  public function init() {
    // Get default properties from project.
    $properties_mapping = array(
      'alias' => 'drush.alias',
      'aliasPath' => 'drush.alias-path',
      'assume' => 'drush.assume',
      'bin' => 'drush.bin',
      'color' => 'drush.color',
      'config' => 'drush.config',
      'pipe' => 'drush.pipe',
      'root' => 'drush.root',
      'simulate' => 'drush.simulate',
      'uri' => 'drush.uri',
      'verbose' => 'drush.verbose',
    );

    foreach ($properties_mapping as $class_property => $drush_property) {
      if (!empty($this->getProject()->getProperty($drush_property))) {
        // TODO: We should use a setter here.
        $this->{$class_property} = $this->getProject()->getProperty($drush_property);
      }
    }
  }

  /**
   * Parameters of the Drush command.
   *
   * @return DrushParam
   *   The created parameter.
   */
  public function createParam() {
    $num = array_push($this->params, new DrushParam());
    return $this->params[$num - 1];
  }

  /**
   * Options of the Drush command.
   *
   * @return DrushOption
   *   The created option.
   */
  public function createOption() {
    $num = array_push($this->options, new DrushOption());
    return $this->options[$num - 1];
  }

  /**
   * The main entry point method.
   */
  public function main() {
    $command = array();

    /**
     * The Drush binary command.
     */
    $command[] = $this->bin;

    /**
     * The site alias.
     */
    if ($this->alias) {
      $command[] = $this->alias;
    }

    /**
     * The options
     */
    if (!$this->color) {
      $option = new DrushOption();
      $option->setName('nocolor');
      $this->options[] = $option;
    }

    if ($this->root) {
      $option = new DrushOption();
      $option->setName('root');
      $option->addText($this->root);
      $this->options[] = $option;
    }

    if ($this->uri) {
      $option = new DrushOption();
      $option->setName('uri');
      $option->addText($this->uri->getAbsolutePath());
      $this->options[] = $option;
    }

    if ($this->config) {
      $option = new DrushOption();
      $option->setName('config');
      $option->addText($this->config);
      $this->options[] = $option;
    }

    if ($this->aliasPath) {
      $option = new DrushOption();
      $option->setName('alias-path');
      $option->addText($this->aliasPath);
      $this->options[] = $option;
    }

    if ($this->assume) {
      $option = new DrushOption();
      $option->setName(($this->assume ? 'yes' : 'no'));
      $this->options[] = $option;
    }

    if ($this->simulate) {
      $option = new DrushOption();
      $option->setName('simulate');
      $this->options[] = $option;
    }

    if ($this->pipe) {
      $option = new DrushOption();
      $option->setName('pipe');
      $this->options[] = $option;
    }

    if ($this->verbose) {
      $option = new DrushOption();
      $option->setName('verbose');
      $this->options[] = $option;
    }

    foreach ($this->options as $option) {
      $command[] = $option->toString();
    }

    /**
     * The Drush command to run.
     */
    $command[] = $this->command;

    /**
     * The parameters.
     */
    foreach ($this->params as $param) {
      $command[] = $param->toString();
    }

    $command = implode(' ', $command);

    if ($this->dir !== NULL) {
      $currdir = getcwd();
      @chdir($this->dir->getPath());
    }

    // Execute Drush.
    $this->log("Executing: " . $command);
    $output = array();
    exec($command, $output, $return);

    if (isset($currdir)) {
      @chdir($currdir);
    }

    // Collect Drush output for display through Phing's log.
    foreach ($output as $line) {
      $this->log($line);
    }

    // Set value of the 'pipe' property.
    if (!empty($this->returnProperty)) {
      $this->getProject()->setProperty($this->returnProperty, implode($this->returnGlue, $output));
    }

    // When build failed.
    if ($this->haltOnError && $return != 0) {
      throw new BuildException("Drush exited with code: " . $return);
    }

    return $return != 0;
  }

}
