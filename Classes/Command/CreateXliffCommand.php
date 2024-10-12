<?php

namespace R3H6\FormTranslator\Command;

use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\LocalizationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
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
        $siteLanguage = new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []);
        $items = $this->formService->getItems($formIdentifier, $siteLanguage);

        $xliff = $this->localizationService->renderXliff([
            'items' => $items,
            'siteLanguage' => $siteLanguage,
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
