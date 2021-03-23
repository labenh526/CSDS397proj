<?php

    interface Command {
        public function execute($args);
        public function argsAreValid($args);
        public function help();
    }

    $exit = new class implements Command {

        public function help() {
            echo "\e[32m exit - exits the program. Takes no arguments. \n\e[39m";
        }

        public function execute($args) {
            exit();
        }

        public function argsAreValid($args) {
            return count($args) == 0;
        }
        
    };

    $help = new class implements Command {

        public function execute($args) {
            echo "\e[34m List of commands:\n\e[37m";
            echo "  exit - exits the program\n";
            echo "  help - lists all commands \n";
            echo "\e[39m\n"; //back to default color
        }

        public function help() {
            echo "\e[32m help - lists all commands. Takes no arguments \n\e[39m";
        }

        public function argsAreValid($args) {
            return count($args) == 0;
        }
    };

    $commands = array("exit" => $exit, "help" => $help);

    while (True) {
        global $commands;
        //User input loop
        $input = rtrim(fgets(STDIN));
        $command = explode(' ', $input)[0];
        if (in_array($command, array_keys($commands))) {
            $args = array_slice(explode(' ', $input), 1);
            if ($commands[$command]->argsAreValid($args))
                $commands[$command]->execute($args);
            else {
                echo "\e[31m Invalid arguments\n";
                $commands[$command]->help(); 
            }
        } else {
            echo "\e[31m Unrecognized Command: \"$input\" Type \"help\" for a list of commands\n\e[39m";
        }
    }
?>