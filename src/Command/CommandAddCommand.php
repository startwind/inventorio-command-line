<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\ExecutableFinder;

class CommandAddCommand extends InventorioCommand
{
    protected static $defaultName = 'command:add';
    protected static $defaultDescription = 'Add a command to the remote console';

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('commandId', null, InputArgument::OPTIONAL, 'The ID of the command')
            ->addOption('command', null, InputArgument::OPTIONAL, 'The command to execute (e.g., "ls")')
            ->addOption('description', null, InputArgument::OPTIONAL, 'Description of the command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $helper = $this->getHelper('question');

        $commands = $this->config->getCommands();

        $commandId = $input->getOption('commandId');
        $command = $input->getOption('command');
        $description = $input->getOption('description');

        if ($commandId) {
            $validationResult = $this->validateCommandId($commandId, $commands, $output);
            if ($validationResult !== true) {
                return $validationResult;
            }
        } else {
            $commandId = $this->askCommandId($input, $output, $helper, $commands);
        }

        if ($command) {
            $validationResult = $this->validateCommand($command, $output);
            if ($validationResult !== true) {
                return $validationResult;
            }
        } else {
            $command = $this->askCommand($input, $output, $helper);
        }

        if ($description) {
            $validationResult = $this->validateDescription($description, $output);
            if ($validationResult !== true) {
                return $validationResult;
            }
        } else {
            $description = $this->askDescription($input, $output, $helper);
        }

        $this->config->addCommand($commandId, $command, $description);

        $output->writeln(['', '']);

        $output->writeln('- Command successfully added');
        $output->writeln('- Running the collect command to sync with inventorio.cloud');

        $this->getApplication()->find('collect')->run(new ArrayInput([]), new NullOutput());

        $output->writeln(['', '']);
        $output->writeln('To run this command via CLI, use the following:');
        $output->writeln('<info>inventorio command:add --commandId="' . $commandId . '" --command="' . $command . '" --description="' . $description . '"</info>');

        return Command::SUCCESS;
    }

    // Validierung für commandId
    private function validateCommandId(string $commandId, array $commands, OutputInterface $output)
    {
        if (array_key_exists($commandId, array_keys($commands))) {
            $output->writeln('<error>There is already a command registered with this command ID. Please try again.</error>');
            return Command::FAILURE;
        }

        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $commandId)) {
            $output->writeln('<error>The Command ID must be alphanumeric and may contain hyphens. Please try again.</error>');
            return Command::FAILURE;
        }

        return true; // Validierung erfolgreich
    }

    // Validierung für command
    private function validateCommand(string $command, OutputInterface $output)
    {
        $finder = new ExecutableFinder();
        $commandParts = explode(' ', $command);
        $mainCommand = $commandParts[0];

        if ($mainCommand && !$finder->find($mainCommand)) {
            $output->writeln("<error>The command '$mainCommand' does not exist. Please try again.</error>");
            return Command::FAILURE;
        }

        return true; // Validierung erfolgreich
    }

    // Validierung für description
    private function validateDescription(string $description, OutputInterface $output)
    {
        if ($this->containsHtmlOrJs($description)) {
            $output->writeln('<error>The description must not contain HTML or JavaScript. Please try again.</error>');
            return Command::FAILURE;
        }

        return true; // Validierung erfolgreich
    }

    private function askCommandId(InputInterface $input, OutputInterface $output, $helper, array $commands): string
    {
        while (true) {
            $commandIdQuestion = new Question('Please enter the Command ID (alphanumeric, hyphens allowed): ');
            $commandId = $helper->ask($input, $output, $commandIdQuestion);

            if (array_key_exists($commandId, array_keys($commands))) {
                $output->writeln('<error>There is already a command registered with this command ID. Please try again.</error>');
                continue;
            }

            if ($commandId !== null && preg_match('/^[a-zA-Z0-9\-]+$/', $commandId)) {
                return $commandId;
            } else {
                $output->writeln('<error>The Command ID must be alphanumeric and may contain hyphens. Please try again.</error>');
            }
        }
    }

    private function askCommand(InputInterface $input, OutputInterface $output, $helper): string
    {
        $finder = new ExecutableFinder();

        while (true) {
            $commandQuestion = new Question('Please enter the command (e.g., "ls"): ');
            $command = $helper->ask($input, $output, $commandQuestion);

            // Überprüfen, ob der Befehl nicht null oder leer ist
            if ($command === null || trim($command) === '') {
                $output->writeln('<error>The command cannot be empty. Please try again.</error>');
                continue;
            }

            // Separate the command from its parameters
            $commandParts = explode(' ', $command);
            $mainCommand = $commandParts[0];

            // Ensure the command is not null and the main command exists
            if ($mainCommand !== null && $finder->find($mainCommand)) {
                return $command;
            } else {
                $output->writeln("<error>The command '$mainCommand' does not exist. Please try again.</error>");
            }
        }
    }

    private function askDescription(InputInterface $input, OutputInterface $output, $helper): string
    {
        while (true) {
            $descriptionQuestion = new Question('Please enter a description (no HTML/JS allowed): ');
            $description = $helper->ask($input, $output, $descriptionQuestion);

            // Ensure the description is not null and doesn't contain HTML/JS
            if ($description !== null && !$this->containsHtmlOrJs($description)) {
                return $description;
            } else {
                $output->writeln('<error>The description must not contain HTML or JavaScript. Please try again.</error>');
            }
        }
    }

    private function containsHtmlOrJs(string $text): bool
    {
        return preg_match('/<[^>]+>/', $text) || preg_match('/<script.*?>.*?<\/script>/is', $text);
    }
}
