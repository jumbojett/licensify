<?php

/*
 * This file is part of the forked package.
 *
 * (c) 2015 Michael Jett
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eo\Licensify;

use Eo\Licensify\Command\LicensifyCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Symfony console application class
 */
class Application extends BaseApplication {
	/**
	 * Overridden so that the application doesn't expect the command
	 * name to be the first argument.
	 */
	public function getDefinition() {
		$inputDefinition = parent::getDefinition();
		// clear out the normal first argument, which is the command name
		$inputDefinition->setArguments();

		return $inputDefinition;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getCommandName(InputInterface $input) {
		return 'licensify';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getDefaultCommands() {
		// Keep the core default commands to have the HelpCommand
		// which is used when using the --help option
		$defaultCommands = parent::getDefaultCommands();

		$defaultCommands[] = new LicensifyCommand();

		return $defaultCommands;
	}
}
