<?php

namespace Billhance\RemoteAssetsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Billhance\Bundle\BootswatchBundle\Exception\InvalidArgumentException;

class RemoteAssetsInstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('billhance:remote_assets:install')
            ->setDefinition(array(
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web'),
            ))
            ->setDescription('Installs remote web assets under a public web directory')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command installs remote assets into a given
directory (e.g. the web directory).

<info>php %command.full_name% web</info>

A "remote_assets" directory will be created inside the target directory.

If a target directory is specified, it will override the default target
from the bundle configuration.

The bundle configuration should contain an array of 'origin' and 'destination'
associative arrays.

'origin' is the file path that will be imported. It supports the same protocols as
file_get_contents.

'destination' is the file path that will be written with the contents of 'origin'.
It will be prepended with the target and 'remote_assets' directory.

<info>billhance_remote_assets:
    target: "web"
    assets:
        bootstrap:
            origin: 'http://example.com/assets/style.css'
            destination: 'example/style.css'
</info>
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getContainer()->getParameter('billhance_remote_assets.config');

        $configTargetArg = rtrim($config['target'], '/');
        $userTargetArg = rtrim($input->getArgument('target'), '/');

        $targetArg = $configTargetArg !== $userTargetArg ? $userTargetArg : $configTargetArg;

        if (!is_dir($targetArg)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The target directory "%s" does not exist.',
                    $input->getArgument('target')
                )
            );
        }

        $filesystem = $this->getContainer()->get('filesystem');

        $assetsDir = $targetArg . '/remote_assets/';

        $filesystem->remove($assetsDir);
        $filesystem->mkdir($assetsDir, 0777);

        foreach ($config['assets'] as $asset) {
            $output->writeln(
                sprintf(
                    'Installing remote assets for <comment>%s</comment> into <comment>%s</comment>',
                    $asset['origin'],
                    $assetsDir . $asset['destination']
                )
            );

            $fileOrigin = $asset['origin'];

            if (!file_exists($asset['origin'])) {
                $fileOrigin = $this->getFileUrl($fileOrigin);

                if (filter_var($fileOrigin, FILTER_VALIDATE_URL) === false) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'File location "%s" is invalid or does not exist.',
                            $fileOrigin
                        )
                    );
                }
            }

            $fileContents = file_get_contents($fileOrigin);

            $filesystem->dumpFile(
                $assetsDir . $asset['destination'],
                $fileContents
            );
        }
    }

    /**
     * @param $url
     *
     * @return string
     */
    protected function getFileUrl($url)
    {
        $parsedUrl = parse_url($url);

        $urlParts = array_map(
            'rawurldecode',
            explode('/', $parsedUrl['path'])
        );

        return $parsedUrl['scheme']
            . '://' . $parsedUrl['host']
            . implode('/', array_map('rawurlencode', $urlParts));
    }
}
