<?php

namespace JGI\Bundle\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GoogleGeoLookupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('google:geolookup')
            ->setDescription('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting geolookup');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $orders = $em->getRepository('JGIAppBundle:Order')->getForGeoLookup(20);

        foreach ($orders as $order) {
            $address = sprintf('%s, %s %s', $order->getStreet(), $order->getZip(), $order->getCity());
            $response = $this->getContainer()
                ->get('buzz.browser')
                ->get(sprintf(
                    'http://maps.googleapis.com/maps/api/geocode/json?address=%s,SE&sensor=false',
                    urlencode($address)
                ))
            ;
            $data = json_decode($response->getContent(), true);
            if (array_key_exists('status', $data)) {
                $order->setGoogleStatus($data['status']);
                if ('OK' == $data['status']) {
                    $order->setLat($data['results'][0]['geometry']['location']['lat']);
                    $order->setLng($data['results'][0]['geometry']['location']['lng']);
                }
                sleep(1);
                $em->persist($order);
                $output->writeln(sprintf('Request for <info>%s</info> with status %s.', $address, $order->getGoogleStatus()));
            } else {
                $output->writeln(sprintf('Error for address <info>%s</info>. Try again later.', $address));
            }
        }
        $em->flush();
        $output->writeln('Finished!');
    }
} 