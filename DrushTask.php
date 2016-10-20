<?php

/**
 * @file
 * A Phing task to run Drush commands.
 */

require_once "phing/Task.php";

/**
 * A Drush CLI parameter.
 */
class DrushParam {

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
  protected $escape;

  /**
   * If TRUE, surround the value with quotes. Otherwise not. Default is TRUE.
   *
   * @var bool
   */
  protected $quote;

  /**
   * Set the escape's param value.
   *
   * @param string|bool $escape
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
    return ($this->escape === 'yes' || $this->escape === 'true');
  }

  /**
   * Set the quote's param value.
   *
   * @param string|bool $quote
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
    return ($this->quote === 'yes' || $this->quote === 'true');
  }

  /**
   * DrushParam constructor.
   */
  public function __construct() {
    $this->setEscape();
    $this->setQuote();
  }

  /**
   * Set the parameter value from a text element.
   *
   * @param mixed $str
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
    $str = '--' . $name;
    if (isset($value) && $value != '') {
      $str .= '="' . escapeshellcmd($value) . '"';
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
   * @var string
   */
  protected $bin = NULL;

  /**
   * URI of the Drupal site to use.
   *
   * @var string
   */
  protected $uri = NULL;

  /**
   * Drupal root directory to use.
   *
   * @var string
   */
  protected $root = NULL;

  /**
   * If set, assume 'yes' or 'no' as answer to all prompts.
   *
   * @var bool
   */
  protected $assume = NULL;

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
   * An array of DrushOption.
   *
   * @var DrushOption[]
   */
  protected $options = array();

  /**
   * An array of DrushParam.
   *
   * @var DrushParam[]
   */
  protected $params = array();

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
   * The Drush command to run.
   */
  public function setCommand($str) {
    $this->command = $str;
  }

  /**
   * Path the Drush executable.
   */
  public function setBin($str) {
    $this->bin = $str;
  }

  /**
   * Drupal root directory to use.
   */
  public function setRoot($str) {
    $this->root = $str;
  }

  /**
   * URI of the Drupal to use.
   */
  public function setUri($str) {
    $this->uri = $str;
  }

  /**
   * Assume 'yes' or 'no' to all prompts.
   */
  public function setAssume($var) {
    if (is_string($var)) {
      $this->assume = ($var === 'yes');
    }
    else {
      $this->assume = !!$var;
    }
  }

  /**
   * Simulate all relevant actions.
   */
  public function setSimulate($var) {
    if (is_string($var)) {
      $var = strtolower($var);
      $this->simulate = ($var === 'yes' || $var === 'true');
    }
    else {
      $this->simulate = !!$var;
    }
  }

  /**
   * Use the pipe option.
   */
  public function setPipe($var) {
    if (is_string($var)) {
      $var = strtolower($var);
      $this->pipe = ($var === 'yes' || $var === 'true');
    }
    else {
      $this->pipe = !!$var;
    }
  }

  /**
   * The 'glue' characters used between each line of the returned output.
   */
  public function setReturnGlue($str) {
    $this->returnGlue = (string) $str;
  }

  /**
   * The name of a Phing property to assign the Drush command's output to.
   */
  public function setReturnProperty($str) {
    $this->returnProperty = $str;
  }

  /**
   * Should the task fail on Drush error (non zero exit code).
   */
  public function setHaltOnError($var) {
    if (is_string($var)) {
      $var = strtolower($var);
      $this->haltOnError = ($var === 'yes' || $var === 'true');
    }
    else {
      $this->haltOnError = !!$var;
    }
  }

  /**
   * Parameters for the Drush command.
   */
  public function createParam() {
    $o = new DrushParam();
    $this->params[] = $o;
    return $o;
  }

  /**
   * Options for the Drush command.
   */
  public function createOption() {
    $o = new DrushOption();
    $this->options[] = $o;
    return $o;
  }

  /**
   * Display extra information about the command.
   */
  public function setVerbose($var) {
    if (is_string($var)) {
      $this->verbose = ($var === 'yes');
    }
    else {
      $this->verbose = !!$var;
    }
  }

  /**
   * Site alias.
   */
  public function setAlias($var) {
    if (is_string($var)) {
      $this->alias = $var;
    }
    else {
      $this->alias = NULL;
    }
  }

  /**
   * Path top an additional config file to load.
   */
  public function setConfig($var) {
    if (is_string($var) && !empty($var)) {
      $this->config = $var;
    }
    else {
      $this->config = NULL;
    }
  }

  /**
   * A list of paths where drush will search for alias files.
   */
  public function setAliasPath($var) {
    if (is_string($var) && !empty($var)) {
      $this->aliasPath = $var;
    }
    else {
      $this->aliasPath = NULL;
    }
  }

  /**
   * Whether or not to use color output.
   */
  public function setColor($var) {
    if (is_string($var) && !empty($var)) {
      $this->color = ($var === 'yes');
    }
    else {
      $this->color = (boolean) $var;
    }
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
   * Initialize the task.
   */
  public function init() {
    // Get default properties from project.
    $this->alias = $this->getProject()->getProperty('drush.alias');
    $this->root = $this->getProject()->getProperty('drush.root');
    $this->uri = $this->getProject()->getProperty('drush.uri');
    $this->bin = $this->getProject()->getProperty('drush.bin');
    $this->config = $this->getProject()->getProperty('drush.config');
    $this->aliasPath = $this->getProject()->getProperty('drush.alias-path');
    $this->color = $this->getProject()->getProperty('drush.color');
  }

  /**
   * The main entry point method.
   */
  public function main() {
    $command = array();

    $command[] = !empty($this->bin) ? '"' . $this->bin . '"' : 'drush';

    if (!empty($this->alias)) {
      $command[] = $this->alias;
    }

    if (empty($this->color)) {
      $option = new DrushOption();
      $option->setName('nocolor');
      $this->options[] = $option;
    }

    if (!empty($this->root)) {
      $option = new DrushOption();
      $option->setName('root');
      $option->addText($this->root);
      $this->options[] = $option;
    }

    if (!empty($this->uri)) {
      $option = new DrushOption();
      $option->setName('uri');
      $option->addText($this->uri);
      $this->options[] = $option;
    }

    if (!empty($this->config)) {
      $option = new DrushOption();
      $option->setName('config');
      $option->addText($this->config);
      $this->options[] = $option;
    }

    if (!empty($this->aliasPath)) {
      $option = new DrushOption();
      $option->setName('alias-path');
      $option->addText($this->aliasPath);
      $this->options[] = $option;
    }

    if (is_bool($this->assume)) {
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

    $command[] = $this->command;

    foreach ($this->params as $param) {
      $command[] = $param->toString();
    }

    $command = implode(' ', $command);

    if ($this->dir !== NULL) {
      $currdir = getcwd();
      @chdir($this->dir->getPath());
    }

    // Execute Drush.
    $this->log("Executing '$command'...");
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
    // Build fail.
    if ($this->haltOnError && $return != 0) {
      throw new BuildException("Drush exited with code $return");
    }
    return $return != 0;
  }

}
