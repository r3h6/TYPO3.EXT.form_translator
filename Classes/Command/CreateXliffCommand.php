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

class CreateXliffCommand extends Command
{
    /**
     * @var FormService
     */
    protected $formService;

    /**
     * @var LocalizationService
     */
    protected $localizationService;

    protected function configure()
    {
        $this
            ->addArgument('formIdentifier', InputArgument::REQUIRED, 'Path to *.form.yaml file.')
            ->addOption('output', 'O', InputOption::VALUE_OPTIONAL, 'Save output to file')
        ;
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
            if (file_put_contents($outputOption, $xliff) === false) {
                return Command::FAILURE;
            }
            return Command::SUCCESS;
        }

        $output->writeln($xliff);
        return Command::SUCCESS;
    }

    public function injectFormService(FormService $formService): void
    {
        $this->formService = $formService;
    }

    public function injectLocalizationService(LocalizationService $localizationService): void
    {
        $this->localizationService = $localizationService;
    }
}
