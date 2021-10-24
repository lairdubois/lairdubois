<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Core\Picture;

class BatchResizeUploadsPicturesCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:batch:rup')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force resizing')
			->setDescription('Resize Uploads Pictures')
			->setHelp(<<<EOT
The <info>ladb:batch:rup</info> command resize uploads pictures
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		// Resizing

		$imagine = new \Imagine\Gd\Imagine();
		$outputSize = new \Imagine\Image\Box(Picture::STORAGE_MAX_WIDTH, Picture::STORAGE_MAX_HEIGHT);
		$pictureCount = 0;
		$resizedPictureCount = 0;

		foreach (glob('uploads/*.{jpeg,png}', GLOB_BRACE) as $path) {
			if ($verbose) {
				$output->write('<info>Resizing '.$path.'... </info>');
			}

			$image = $imagine->open($path);
			$inputSize = $image->getSize();

			if ($verbose) {
				$output->write('<comment>('.$inputSize->getWidth().'x'.$inputSize->getHeight().') </comment>');
			}

			if ($inputSize->getWidth() > $outputSize->getWidth() || $inputSize->getHeight() > $outputSize->getHeight()) {
				$resizedPictureCount++;
				if ($forced) {
					$image
						->thumbnail($outputSize, \Imagine\Image\ImageInterface::THUMBNAIL_INSET)
						->save($path)
					;
				}
				if ($verbose) {
					$output->writeln('<fg=black;bg=cyan>[Bigger]</fg=black;bg=cyan>');
				}
			} else {
				if ($verbose) {
					$output->writeln('<fg=cyan>[Smaller]</fg=cyan>');
				}
			}
			$pictureCount++;

			unset($image);

		}

		if ($forced) {
			$output->writeln('<info>'.$resizedPictureCount.'/'.$pictureCount.' pictures resized</info>');
		} else {
			$output->writeln('<info>'.$resizedPictureCount.'/'.$pictureCount.' pictures to resize</info>');
		}

		return Command::SUCCESS;
	}

}