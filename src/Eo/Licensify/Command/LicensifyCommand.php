<?php

/*
 * This file is part of the forked package.
 *
 * (c) 2015 Michael Jett
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This file is part of the ACE (Analytics Collaboration Environment) package.
 *
 * (c) 2015 The MITRE Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eo\Licensify\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Licensify command
 */
class LicensifyCommand extends Command {
	/**
	 * {@inheritdoc}
	 */
	protected function configure() {
		$this
			->setName('licensify')
			->setDescription('Automatically add license headers to your PHP source files')
			->addOption('cwd', 'w', InputOption::VALUE_REQUIRED, 'Current working directory', './')
			->addOption('package', 'p', InputOption::VALUE_REQUIRED, 'Package name', 'Licensify')
			->addOption('author', 'a', InputOption::VALUE_REQUIRED, 'The author to use.', 'Eymen Gunay <eymen@egunay.com>')
		;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$finder = new Finder();

		$path = $input->getOption('cwd');

		if (file_exists($path) && !is_dir($path)) {
			$finder->append([0 => $path]);
		} else {
			$finder
				->files()
				->in($path)
				->name('*.php');
		}

		$license = $this->getLicenseText($input->getOption('package'), $input->getOption('author'));

		$t = 0;

		foreach ($finder as $file) {
			$data = file_get_contents($file->getRealpath());
			$tokens = token_get_all($data);

			$content = '';
			$afterNamespace = $afterClass = $ignoreWhitespace = false;

			for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
				if (!is_array($tokens[$i])) {
					$content .= $tokens[$i];
					continue;
				}

				if (($afterNamespace || $afterClass)
					&& (T_COMMENT === $tokens[$i][0] && $this->isOldLicense($tokens[$i][1]))) {
					print_r($tokens[$i]);
					continue;
				}

				if (T_NAMESPACE === $tokens[$i][0]) {
					$content .= "\n" . $license . "\n\n";
					$afterNamespace = true;
				}

				if (T_CLASS === $tokens[$i][0]) {
					$afterClass = true;
				}

				$content .= $tokens[$i][1];
			}

			if (false === $afterNamespace) {
				$regex = '~(?P<group>^\s*<\?php(((/\*.*?\*/)|((//|#).*?\n{1})|(\s*))*))~s';
				preg_match($regex, $data, $results);

				if (!isset($results['group'])) {
					continue;
				}

				$result = $results['group'];

				// If this is not a license comment
				if ($this->isOldLicense($result)) {
					$content = str_replace($result, "<?php\n\n" . trim($license) . "\n\n", $data);
				} else {
					$content = preg_replace('/<\?php/', "<?php\n\n" . trim($license) . "\n\n", $data, 1);
				}
			}

			file_put_contents($file->getRealpath(), $content);

			$output->writeln(sprintf('[Modify] <comment>%s</comment>', $file->getPathname()));

			++$t;
		}

		$output->writeln("<info>Command finished successfully. Licensified $t files.</info>");
	}

	/**
	 * Get license text
	 *
	 * @param  string   $package
	 * @param  string   $author
	 * @return string
	 */
	protected function getLicenseText($package, $author) {
		$text = [
			'/*',
			" * This file is part of the $package package.",
			' *',
			" * (c) $author",
			' *',
			' * For the full copyright and license information, please view the LICENSE',
			' * file that was distributed with this source code.',
			' */',
		];

		return implode(PHP_EOL, $text);
	}

	/**
	 * @param $text
	 */
	protected function isOldLicense($text) {
		return (strpos($text, 'please view the LICENSE') !== false);
	}
}
