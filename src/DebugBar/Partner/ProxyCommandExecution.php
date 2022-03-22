<?php

namespace DebugBar\Partner;

use BeSmartee\Utils\Validation\ValidationInterface;

class ProxyCommandExecution
{
    private $command;
    private $args = [];
    private $options = [];
    private $validationError = [];
    private $allowRunningOnLocalhost = true;
    private $outputResult = '';
    private $outputError = '';

    /**
     * @var string Separator between command's option and its value
     *      Example:
     *              <option> <value> - separator is space
     *              <option><value> - separator is empty
     *              <option>=<value> - separator is =
     */
    private $optionAndValueSeparator = ' ';

    /**
     * @var bool
     * Options or arguments will be put right after command
     */
    private $isOptionFirst = true;

    public function __construct( $command = '', array $args = [], array $options = [] ) {
        $this->command = $command;
        $this->args = $args;
        $this->options = $options;

        $this->setOutputError('/dev/null');
    }

    /**
     * @param $command
     * @return CommandExecution
     */
    public function buildCommand( $command ) {
        $this->command = $command;

        return $this;
    }

    /**
     * Set argument for command
     *
     * @param $value
     * @param null|array|ValidationInterface|string $validation
     *        Possible values:
     *          null
     *          array of ValidationInterface
     *          string - class name
     *          instance of ValidationInterface
     *
     * @return CommandExecution
     */
    public function withArgument( $value, $validation ) {
        $value = (string)$value;
        if ( $this->validate( $value, $validation ) ) {
            $this->args[] = $value;
        }
        else {
            $this->validationError[] = "Argument {$value} is invalid";
        }
        return $this;
    }

    /**
     * Set option of command
     *
     * @param $name
     * @param null $value
     * @param null|array|ValidationInterface|string $validation
     *        Possible values:
     *          null
     *          array of string or ValidationInterface
     *          string - class name
     *          instance of ValidationInterface
     *
     * @return CommandExecution
     */
    public function withOption( $name, $value = null, $validation = null ) {
        if ( !is_null( $value ) ) {
            $value = (string)$value;
            if ( !$this->validate( $value, $validation ) ) {
                $this->validationError[] = "Value {$value} of option {$name} is invalid";
            }
        }

        $this->options[$name] = $value;
        return $this;
    }

    /**
     * Set options right after command
     * @return CommandExecution
     */
    public function optionFirst() {
        $this->isOptionFirst = true;

        return $this;
    }

    /**
     * Set arguments right after command
     * @return CommandExecution
     */
    public function argumentFirst() {
        $this->isOptionFirst = false;

        return $this;
    }

    /**
     * @param string $separator
     * @return CommandExecution
     */
    public function setOptionValueSeparator( string $separator = ' ' ) {
        $this->optionAndValueSeparator = $separator;

        return $this;
    }

    /**
     * @param string $fileName
     * @param bool $isOverride
     * @return CommandExecution
     */
    public function setOutputResult(string $fileName, $isOverride = true ) {
        if ( empty( $fileName ) ) {
            $this->outputResult = '';
        } else {
            if ( $isOverride ) {
                $this->outputResult = "> {$fileName}";
            }
            else {
                $this->outputResult = ">> {$fileName}";
            }
        }

        return $this;
    }

    /**
     * @param string $fileName
     * @param bool $isOverride
     * @return CommandExecution
     */
    public function setOutputError(string $fileName, $isOverride = true ) {
        if ( empty( $fileName ) ) {
            $this->outputError = '';
        } else {
            if ( $isOverride ) {
                $this->outputError = "2> {$fileName}";
            }
            else {
                $this->outputError = "2>> {$fileName}";
            }
        }

        return $this;
    }

    /**
     * Do not run this command on localhost
     * @return CommandExecution
     */
    public function excludeLocalhost() {
        $this->allowRunningOnLocalhost = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReadyToRun() {
        return empty( $this->validationError );
    }

    /**
     * Return validation errors
     *
     * @return string
     */
    public function getError() {
        return implode( "\n", $this->validationError );
    }

    /**
     * @return array
     */
    public function getRawCommand() {
        return [
            'command' => $this->command,
            'options' => $this->options,
            'optionAndValueSeparator' => $this->optionAndValueSeparator,
            'arguments' => $this->args,
            'isAllowRunOnLocalhost' => $this->allowRunningOnLocalhost,
            'isOptionFirst' => $this->isOptionFirst,
        ];
    }

    /**
     * Execute the command
     *
     * @param bool $isAsync
     * @param null $output
     * @param null $returnVar
     */
    function run( $isAsync = true, &$output = null, &$returnVar = null ) {
        if ( $this->isReadyToRun() && ($this->allowRunningOnLocalhost || !\Helper::isLocalhost()) ) {
            $command = $this->prepareCommand( $isAsync );
            exec( $command, $output, $returnVar );
        } else {
            $returnVar = 126; // Means "Command invoked cannot execute"
            if( !$this->allowRunningOnLocalhost && \Helper::isLocalhost() ) {
                $output = 'The command is not allow to run on localhost or Windows';
            } else {
                $output = $this->getError();
            }
        }
    }

    /**
     * @return string
     */
    private function buildOptions() {
        $builtList = [];
        foreach ( $this->options as $name => $value ) {
            $name = $this->trim( $name );
            if ( substr( $name, 0, 1 ) !== '-' ) {
                $name = "-{$name}";
            }

            if ( !is_null( $value ) ) {
                $value = escapeshellarg( $this->trim( $value ) );
                $builtList[] = "{$name}{$this->optionAndValueSeparator}{$value}";
            }
            else {
                $builtList[] = $name;
            }
        }

        return implode( ' ', $builtList );
    }

    /**
     * @return string
     */
    public function buildArguments() {
        $builtList = [];
        foreach ( $this->args as $arg ) {
            $builtList[] = escapeshellarg( $this->trim( $arg ) );
        }
        return implode( ' ', $builtList );
    }

    /**
     * @param $value
     * @return string
     */
    private function trim( $value ) {
        // trim white space and also double quote. Only using single quote for option's value and arguments
        return trim( $value, " \t\n\r\0\x0B\"\'" );
    }

    /**
     * @param $value
     * @param $validation
     * @return bool
     */
    private function validate( $value, $validation ) {
        $result = true;
        if ( is_array( $validation ) ) {
            foreach ( $validation as $item ) {
                $result = $result && $this->validate( $value, $item );
            }
        }
        elseif ( is_subclass_of( $validation, ValidationInterface::class ) ) {
            if ( is_object( $validation ) ) {
                $result = $validation->validate( $value );
            }
            elseif
            ( is_string( $validation ) ) {
                $instance = new $validation();
                $result = $instance->validate( $value );
            }
            else {
                $result = false;
            }
        }
        else {
            $result = false;
        }

        return $result;
    }

    /**
     * @param $isAsync
     * @return string
     */
    private function prepareCommand( $isAsync ) {
        $command = escapeshellarg($this->trim( $this->command ));
        $options = $this->buildOptions();
        $args = $this->buildArguments();

        //todo parse class and params
        event()->dispatch('command', [$command, $options, $args]);

        if ( $this->isOptionFirst ) {
            $command = "{$command} {$options} {$args}";
        } else {
            $command = "{$command} {$args} {$options}";
        }

        if ( !\Helper::isWindows() ) {

            if (empty($this->outputResult) && $isAsync) {
                // If this command is asynchronous and no output stream specified, then we will set output stream of this command as `/dev/null`
                $this->setOutputResult('/dev/null');
            }

            if (!empty( $this->outputResult )) {
                $command .= " {$this->outputResult}";
            }

            if (!empty($this->outputError)) {
                $command .= " {$this->outputError}";
            }

            if ( $isAsync ) {
                $command .= ' &';
            }
        }

        return $command;
    }
    /**
     * @return int
     */
    public function getArgumentCount(){
        return count($this->args);
    }
}