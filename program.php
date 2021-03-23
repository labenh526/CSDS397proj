<?php

    error_reporting(E_ERROR | E_PARSE);    //Suppresses php warnings (comment out line for development)

    $fileHeaders;
    $fileData;

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
            global $commands;
            if (count ($args) == 0) {
                echo "\e[34m List of commands:\n\e[37m";
                echo "  display - displays the currently loaded file (see \"help display\" for details)\n";
                echo "  exit - exits the program\n";
                echo "  help - lists all commands \n";
                echo "  open - loads a csv file\n";
                echo "\e[39m\n"; //back to default color
            } else {
                $commands[$args[0]]->help();
            }
        }

        public function help() {
            echo "\e[32m help - lists all commands  \n help [command] - displays information about the given command\e[39m\n";
        }

        public function argsAreValid($args) {
            global $commands;
            return count($args) == 0 || (count($args) == 1 && in_array($args[0], array_keys($commands)));
        }
    };

    $open = new class implements Command {
        public function execute($args) {
            global $fileHeaders;
            global $fileData;
            if (($handle = fopen($args[0], "r")) !== FALSE) {
                if (($fileHeaders = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    $i = 0;
                    while (($fileData[$i++] = fgetcsv($handle, 1000, ";")) !== FALSE) {}
                    echo "File loaded successfully\n";
                } else {
                    echo "\e[31m error reading file\n\e[39m";
                }
            } else {
                echo "\e[31m error opening file\n\e[39m";
            }
        }

        public function help() {
            echo "\e[32m open [file] - loads a csv file into the program. \nThe first line of the file should be the headers with the remaining lines containing the data \n\e[39m";
        } 

        public function argsAreValid($args) {
            return count($args) == 1;
        }
    };

    $display = new class implements Command {
        public function execute($args) {
            global $fileData, $fileHeaders;
            if (is_null($fileHeaders) || is_null($fileData)) {
                echo "\e[31m error: no file loaded into program. Use \"open\" to load a file.\n\e[39m";
            } elseif (count($args) == 0) {
                //Display the currently loaded csv file
                $this->standardDisplay();
            } 
        }

        public function help() {
            echo "\e[32m display - displays all information in the currently loaded file";
            echo"\n\e[39m";
        } 

        public function argsAreValid($args) {
            return count($args) == 0;
        }

        function standardDisplay() {
            global $fileData, $fileHeaders;
            foreach ($fileData as &$dataline) {
                if (is_array($dataline)) {
                    $i = 0;
                    foreach ($fileHeaders as &$header) {
                        echo "\e[96m"; //Change color to light cyan
                        printf("%s: \e[37m%s", $header, $dataline[$i++]);
                        echo "\n";
                    }
                    echo "\n";
                }
            }
            echo "\e[39m";
        }
    };

    $commands = array("exit" => $exit, "help" => $help, "open" => $open, "display" => $display);

    while (True) {
        global $commands;
        //User input loop
        echo ">";
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