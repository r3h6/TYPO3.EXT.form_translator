<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Command;

use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CreateXliffCommand extends Command
{
    public function __construct(
        protected readonly FormService $formService,
        protected readonly LocalizationService $localizationService,
    ) {
        parent::__construct();
    }

    /**
     * @phpstan-return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Prints an xliff file with all found sources for a given form.')
            ->addArgument('formIdentifier', InputArgument::REQUIRED, 'Path to *.form.yaml file.')
            ->addOption('output', 'O', InputOption::VALUE_OPTIONAL, 'Save output to a file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formIdentifier = $input->getArgument('formIdentifier');
        $language = new Typo3Language('default', 'English', 'gb');
        $items = $this->formService->getItems($formIdentifier, $language);

        $xliff = $this->localizationService->renderXliff([
            'items' => $items,
            'language' => $language,
            'originalFile' => '',
        ]);

        $outputOption = $input->getOption('output');
        if ($outputOption) {
            if (GeneralUtility::writeFile($outputOption, $xliff) === false) {
                return Command::FAILURE;
            }
            return Command::SUCCESS;
        }

        $output->writeln($xliff);
        return Command::SUCCESS;
    }
}
