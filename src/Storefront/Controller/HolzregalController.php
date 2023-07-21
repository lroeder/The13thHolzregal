<?php

namespace The13thHolzregal\Storefront\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;


/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class HolzregalController extends StorefrontController
{

    private $bearerToken;
    private $rAbstand;

    private $genericPageLoader;
    private LineItemFactoryRegistry $factory;
    private CartService $cartService;

    public function __construct( 
        GenericPageLoaderInterface $genericPageLoader, LineItemFactoryRegistry $factory, 
        CartService $cartService)
    {
        $this->genericPageLoader = $genericPageLoader;
        
        $this->factory = $factory;
        $this->cartService = $cartService;
    }

    /**
     * @Route("/konfigurator", name="frontend.the13thholzregal.holzregal", methods={"GET","POST"}, defaults={"XmlHttpRequest"=true})
     */

    
    public function holzregalPage(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->genericPageLoader->load($request, $context);

        if(isset($_POST['addCard'])) {
            $this->addCart($context);
            unset($_SESSION['holzregal']['id']);
            unset($_POST['regal']);
        }



        $this->holzregalId();
        
        if(isset($_POST['regal']['addsub'])) {
            $_POST['regal']['anzahl'] = $_POST['regal']['anzahl'] + $_POST['regal']['addsub'];
        }

        return $this->renderStorefront('@the13thholzregal/storefront/page/holzregal/konfig.html.twig', [
            'page' => $page,
            'konfig_id' => $_SESSION['holzregal']['id'],
            'session_id' => session_id(),
            'regalVar' => array(
                'tiefe' => array(22,30,40,50,60,70),
                'oberflaeche' => array('u', 'k'),
                'oberflaechename' => array(
                    'u' => 'unbehandelt',
                    'k' => 'klar lackiert'
                ),
                'hoehe' => [89,149,189,209,229,249,259,289],
                'breite' => [50,70,80,100,120],
                'stuetzart' => ['Diagonalkreuz', 'Traverse']
            ),
            'regal' => $this->getRegalAufbau(),
            'orderArt' => $this->getArtikelItems(),
            'svg' => $_SESSION['holzregal']['svg'],
            'rAbstand' => $this->rAbstand
        ]);
    }

    private function holzregalId() {
        if(!isset($_SESSION['holzregal']['id'])) {
            $lfdKonfig = file_get_contents('bundles/the13thholzregal/lfd.txt');
            $lfdKonfig++;
            file_put_contents('bundles/the13thholzregal/lfd.txt', ($lfdKonfig));
            $_SESSION['holzregal']['id'] = $lfdKonfig;
        } 
    }

    private function addCart(SalesChannelContext $context) {
        //$cart = $this->cartService;
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $artikelItems = $this->getArtikelItems();

        $xItems = count($artikelItems['items']);
        $xItem = 1;
        foreach($artikelItems['items'] AS $rowArt =>  $rowCount) {
            $lineItem = $this->factory->create([
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, // Results in 'product'
                'referencedId' => $rowArt, // this is not a valid UUID, change this to your actual ID!
                'quantity' => $rowCount,
                'stackable' => false,
                'payload' => [
                    'custom_regalconfig_id' => $_SESSION['holzregal']['id'],
                    'custom_regalconfig_mass' => $artikelItems['mass'],
                    'custom_regalconfig_item' => "Position {$xItem} von {$xItems} ",
                ]
            ], $context);
            $lineItem->setStackable(false);
            $this->cartService->add($cart, $lineItem, $context);
            $xItem++;
        }
    }

    private function getArtikelItems() {
        $artRegal = [];

        $artikel = $this->getArtikel();

        $regal = $this->getRegalAufbau();
    
        foreach($regal['warenkorb'] AS $key => $value) {
            $artRegal['items'][$artikel[$key]['articleID']] = $value * 1;
        }
        $artRegal['mass'] = "{$regal['gesamtBreite']} x {$regal['tiefe']} x {$regal['gesamtHoehe']} cm";
        return $artRegal;
    }

    private function getRegalAufbau() {
        if(!isset($_POST['regal'])) {
            $_POST['regal'] = array(
                'anzahl' => 1,
                'tiefe' => 30,
                'oberflaeche' => 'u',
                'stuetzart' => 'Diagonalkreuz',
                'aufbau' => array(
                    1 => array(
                        'hoehe' => 229,
                        'breite' => 100,
                        'boden' => 6
                    ),
                    2 => array(
                        'hoehe' => 229,
                        'breite' => 100,
                        'boden' => 6
                    )
                )           
            );
        } else if(($_POST['regal']['anzahl'] + 1) < count($_POST['regal']['aufbau'])) {
            unset($_POST['regal']['aufbau'][($_POST['regal']['anzahl']+2)]);
        } else {
            $_POST['regal']['aufbau'][$_POST['regal']['anzahl'] + 1]['hoehe'] = $_POST['regal']['aufbau'][$_POST['regal']['anzahl']]['hoehe'];
            $_POST['regal']['aufbau'][$_POST['regal']['anzahl'] + 1]['boden'] = $_POST['regal']['aufbau'][$_POST['regal']['anzahl']]['boden'];
            $_POST['regal']['aufbau'][$_POST['regal']['anzahl'] + 1]['breite'] = $_POST['regal']['aufbau'][$_POST['regal']['anzahl']]['breite'];    
        }        
        ksort($_POST['regal']['aufbau']);

        $stuetzart = $_POST['regal']['stuetzart'];
        //$stuetzart = 'Diagonalkreuz';
        //'Diagonalkreuz';
        //$stuetzart = 'Traverse';
        
        $vFactor = 1.3;

        $vAbstandOben = 20+50;
        $vAbstandLinks = 150;
        $vBreite = 0;
        $vFormHoehe = '';
        $vFormBreite = '';
        $vFormAnzahl  = '';
        $vFormAnzahlO  = '';
        $aStuetze = '';
        $aStaender = '';
        $aBoeden = '';
        $aOrdner = '';
        $XXBreite = 0;
        $XXx1 = 0;
        $YYx1 = 0;
        $XYAnzahl = 0;
        if($stuetzart == 'Diagonalkreuz') {
        $wkorb['klara'] = 0;
        } else {
        $wkorb['oskar'] = 0;
        }
        $wkorb['SW10001'] = 0;


        foreach($_POST['regal']['aufbau'] AS $key => $value) {
            $x = $key;
            if($_POST['regal']['anzahl'] >= $key) {
                $XXBreite = $XXBreite + $value['breite'];
            }

            if($key <= $_POST['regal']['anzahl']) {
                if($value['hoehe'] > $_POST['regal']['aufbau'][$key]['hoehe']) {
                    $xHoeheL = $value['hoehe'];
                } else if($value['hoehe'] < $_POST['regal']['aufbau'][$key]['hoehe']) {
                    $xHoeheL = $value['hoehe'];
                } else {
                    $xHoeheL = $value['hoehe'];
                }
                
                
                $vBreite = $vBreite + $value['breite'];
        
                if($_POST['regal']['anzahl'] % 2 != 0) {
                    if($stuetzart == 'Traverse' && (($x == 2 || $x == 4 || $x == 6 || $x == 8 || $x == 10 || $x == 12) OR $_POST['regal']['anzahl'] == 1)) {
                        $wkorb['oskar'] = $wkorb['oskar'] +1;
                        $aStuetze .= '  <line x1="'.(2.25*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.((5*$vFactor + $vAbstandOben) + (289*$vFactor - $value['hoehe']*$vFactor)).'" x2="'.(2.25*$vFactor + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.((5*$vFactor + $vAbstandOben) + (289*$vFactor - $value['hoehe']*$vFactor)).'" style="stroke:#ababab;stroke-width:10;" />
                                <line x1="'.(2.25*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.(284*$vFactor + $vAbstandOben).'" x2="'.(2.25*$vFactor + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.(284*$vFactor + $vAbstandOben).'" style="stroke:#ababab;stroke-width:10;" />';
                    } else if($stuetzart == 'Diagonalkreuz' && ((  $x == 2 || $x == 4 || $x == 6 || $x == 8 || $x == 10 || $x == 12) OR $_POST['regal']['anzahl'] == 1)) {
                        $wkorb['klara'] = $wkorb['klara'] +1;
                        $aStuetze .= '  <line x1="'.(2.25*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.((5*$vFactor + $vAbstandOben) + (289*$vFactor - $value['hoehe']*$vFactor)).'" x2="'.(2.25*$vFactor + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.(284*$vFactor + $vAbstandOben).'" style="stroke:#ababab;stroke-width:1;" />
                                <line x1="'.(2.25*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.(284*$vFactor + $vAbstandOben).'" x2="'.(2.25*$vFactor + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.((5*$vFactor + $vAbstandOben) + (289*$vFactor - $value['hoehe']*$vFactor)).'" style="stroke:#ababab;stroke-width:1;" />';
                    }
                } else {
                    if($stuetzart == 'Traverse' && ($x == 1 || $x == 3 || $x == 5 || $x == 7 || $x == 9 || $x == 11)) {
                        $wkorb['oskar'] = $wkorb['oskar'] +1;
                        $aStuetze .= '  <line x1="'.(2.25*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.((5*$vFactor + $vAbstandOben) + (289*$vFactor - $value['hoehe']*$vFactor)).'" x2="'.(2.25*$vFactor + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.((5*$vFactor + $vAbstandOben) + (289*$vFactor - $value['hoehe']*$vFactor)).'" style="stroke:#ababab;stroke-width:10;" />
                                <line x1="'.(2.25*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.(284*$vFactor + $vAbstandOben).'" x2="'.(2.25*$vFactor + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.(284*$vFactor + $vAbstandOben).'" style="stroke:#ababab;stroke-width:10;" />';
                    } else if($stuetzart == 'Diagonalkreuz' && ($x == 1 || $x == 3 || $x == 5 || $x == 7 || $x == 9 || $x == 11)) {
                        $wkorb['klara'] = $wkorb['klara'] +1;
                        $aStuetze .= '  <line x1="'.(2.25*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.((5*$vFactor + $vAbstandOben) + (289*$vFactor - $value['hoehe']*$vFactor)).'" x2="'.(2.25*$vFactor + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.(284*$vFactor + $vAbstandOben).'" style="stroke:#ababab;stroke-width:1;" />
                                <line x1="'.(2.25*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.(284*$vFactor + $vAbstandOben).'" x2="'.(2.25*$vFactor + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.((5*$vFactor + $vAbstandOben) + (289*$vFactor - $value['hoehe']*$vFactor)).'" style="stroke:#ababab;stroke-width:1;" />';
                    }
                }		
                
                    $B[$key]['L'] = $xHoeheL;
                    $XXx1 = (2.25*$vFactor + ($vBreite - $value['breite']) + $vAbstandLinks);
                    $aStaender .= '<line x1="'.(2.25*$vFactor + ($vBreite - $value['breite'])*$vFactor + $vAbstandLinks).'" y1="'.($vAbstandOben + (289*$vFactor - $xHoeheL*$vFactor)).'" x2="'.(2.25*$vFactor + ($vBreite - $value['breite'])*$vFactor + $vAbstandLinks).'" y2="'.(289*$vFactor + $vAbstandOben).'" style="stroke:#d1903d;stroke-width:'.(4.5*$vFactor).';" />';
        
                    $B[$key]['R'] = $value['hoehe'];
                    $YYx1 = (2.25*$vFactor + ($vBreite) + $vAbstandLinks);
                $aStaender .= '<line x1="'.(2.25*$vFactor + ($vBreite*$vFactor) + $vAbstandLinks).'" y1="'.($vAbstandOben + (289*$vFactor - $value['hoehe']*$vFactor)).'" x2="'.(2.25*$vFactor + ($vBreite*$vFactor) + $vAbstandLinks).'" y2="'.(289*$vFactor + $vAbstandOben).'" style="stroke:#d1903d;stroke-width:'.(4.5*$vFactor).';" />';
                
                
                $vAbstand = $value['hoehe']*$vFactor / $value['boden'];
                for($y=1;$y<=$value['boden'];$y++) {
                    if($y==1) {
                        $aBoeden .= '<line x1="'.(0 + $vAbstandLinks + 2.25).'" y1="'.(289 + $vAbstandOben + 100).'" x2="'.(0 + $vAbstandLinks + 2.25).'" y2="'.(289 + $vAbstandOben + 110).'" style="stroke:#a5c614;stroke-width:1" />';
                    } else {
                        $aBoeden .= '<line x1="'.($XXBreite*$vFactor + 2.25*$vFactor + $vAbstandLinks-1).'" y1="'.(289 + $vAbstandOben + 100).'" x2="'.($XXBreite*$vFactor + 2.25*$vFactor + $vAbstandLinks-1).'" y2="'.(289 + $vAbstandOben + 110).'" style="stroke:#a5c614;stroke-width:1" />';//#a5c614
                        $aBoeden .= '<text x="'.((2.25 + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks) + ($value['breite']/2-10)).'" y="'.(289 + $vAbstandOben + 103).'" fill="#a5c614">'.((($value['breite']))).' cm</text>';
                        $aBoeden .= '<text x="'.((2.25 + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks) + ($value['breite']/2-35)).'" y="'.(289 + $vAbstandOben + 118).'" fill="#a5c614">Lichte '.((($value['breite'])) - 2.25).' cm</text>';
                        $aBoeden .= '<line x1="'.(2.25 + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.(289 + $vAbstandOben + 105).'" x2="'.(2.25 + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.(289 + $vAbstandOben + 105).'" style="stroke:#ababab;stroke-width:0.5;" />';//#ffcc52
                    }
                    if(!isset($wkorb['20.'.($value['breite']*10).'.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']])) {
                        $wkorb['20.'.($value['breite']*10).'.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']] = 0;
                        //$wkorb['20.'.($value['breite']*10).'.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]['bez'] = 'Boden '.$value['breite'].'x'.$_POST['regal']['tiefe'].' cm';
                    }
                    $wkorb['20.'.($value['breite']*10).'.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']] = $wkorb['20.'.($value['breite']*10).'.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']] + 1;
                    $aBoeden .= '<line x1="'.(2.25 + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks).'" y1="'.((280*$vFactor + $vAbstandOben) - ($vAbstand * ($y - 1))).'" x2="'.(2.25 + $vBreite*$vFactor + $vAbstandLinks).'" y2="'.((280*$vFactor + $vAbstandOben) - ($vAbstand * ($y - 1))).'" style="stroke:#ffcc52;stroke-width:3;" />';//#ffcc52
                    for($z=0;$z<=($value['breite']*$vFactor - 8.5*$vFactor);) {
                        $XYAnzahl = $XYAnzahl +1;
                        //$aOrdner .= ' <image x="'.(5*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks + $z).'" y="'.((246*$vFactor + $vAbstandOben) - ($vAbstand * ($y - 1))).'" width="'.(8.1*$vFactor).'" height="'.(32*$vFactor).'" xlink:href="grafik/Ordner.png" />';
                        //$aOrdner .= ' <rect x ="'.(5*$vFactor + ($vBreite*$vFactor - $value['breite']*$vFactor) + $vAbstandLinks + $z).'" y ="'.((246*$vFactor + $vAbstandOben) - ($vAbstand * ($y - 1))).'" width ="'.(8.1*$vFactor).'" height ="'.(32*$vFactor).'" rx ="'.(0.5*$vFactor).'" ry ="'.(0.5*$vFactor).'" fill="url(#picture)"  style="stroke:black;stroke-width:'.(0.2*$vFactor).';"/>';
                        $z = $z + 8.6*$vFactor;
                        }
                }

            }
            
        
        }

        $xxHoehe = 0;
        $yyHoehe = 0;
        $SLP = 'NEIN';
        
        for($xB = 1; $xB <= count($B); $xB++) {
            if($yyHoehe < $B[$xB]['L']) {
                $yyHoehe = $B[$xB]['L'];
            }
            if($xB == 1) {
                //echo $xB."-".$B[$xB]['L']."<br>";
                if(!isset($wkorb[($B[$xB]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']])) 
                    {	$wkorb[($B[$xB]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= 0;
                        //$wkorb[($B[$xB]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]['bez'] = 'Ständer '.$B[$xB]['L'].'x'.$_POST['regal']['tiefe'].' cm';
                        if($xxHoehe < $B[$xB]['L']) {$xxHoehe = $B[$xB]['L'];}
                    }
                $wkorb[($B[$xB]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= $wkorb[($B[$xB]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]+ 1;
                $wkorb['SW10001']= $wkorb['SW10001']+ 1;
                if(isset($B[$xB+1]['L']) AND $B[$xB]['R'] < $B[$xB+1]['L']) {
                    //echo $xB."-1-".$B[$xB+1]['L']."<br>";
                    if(!isset($wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']])) 
                        {	$wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= 0;
                            //$wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]['bez'] = 'Ständer '.$B[$xB+1]['L'].'x'.$_POST['regal']['tiefe'].' cm';
                            if($xxHoehe < $B[$xB+1]['L']) {$xxHoehe = $B[$xB+1]['L'];}
                        }
                    $wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= $wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]+ 1;
                $wkorb['SW10001']= $wkorb['SW10001']+ 1;
                } else {
                    //echo $xB."-2-".$B[$xB]['R']."<br>";
                    if(!isset($wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']])) 
                        {	$wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= 0;
                            //$wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]['bez'] = 'Ständer '.$B[$xB]['R'].'x'.$_POST['regal']['tiefe'].' cm';
                            if($xxHoehe < $B[$xB]['R']) {$xxHoehe = $B[$xB]['R'];}
                        }
                    $wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= $wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]+ 1;
                $wkorb['SW10001']= $wkorb['SW10001']+ 1;
                }
            } else if($xB > 1 AND $xB < count($B)) {
                if($B[$xB]['R'] < $B[$xB+1]['L']) {
                    //echo $xB."-1-".$B[$xB+1]['L']."<br>";
                    if(!isset($wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']])) 
                        {	$wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= 0;
                            //$wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]['bez'] = 'Ständer '.$B[$xB+1]['L'].'x'.$_POST['regal']['tiefe'].' cm';
                            if($xxHoehe < $B[$xB+1]['L']) {$xxHoehe = $B[$xB+1]['L'];}
                        }
                    $wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= $wkorb[($B[$xB+1]['L']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]+ 1;
                $wkorb['SW10001']= $wkorb['SW10001']+ 1;
                } else {
                    //echo $xB."-2-".$B[$xB]['R']."<br>";
                    if(!isset($wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']])) 
                        {	$wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= 0;
                            //$wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]['bez'] = 'Ständer '.$B[$xB]['R'].'x'.$_POST['regal']['tiefe'].' cm';
                            if($xxHoehe < $B[$xB]['R']) {$xxHoehe = $B[$xB]['R'];}
                        }
                    $wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= $wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]+ 1;
                $wkorb['SW10001']= $wkorb['SW10001']+ 1;
                }	
            } else if($xB == count($B)) {
                    //echo $xB."-".$B[$xB]['R']."<br>";
                    if(!isset($wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']])) 
                        {	$wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= 0;
                            //$wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]['bez'] = 'Ständer '.$B[$xB]['R'].'x'.$_POST['regal']['tiefe'].' cm';
                            if($xxHoehe < $B[$xB]['R']) {$xxHoehe = $B[$xB]['R'];}
                        }
                    $wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]= $wkorb[($B[$xB]['R']*10).'.35.'.($_POST['regal']['tiefe']*10).'.'.$_POST['regal']['oberflaeche']]+ 1;
                $wkorb['SW10001']= $wkorb['SW10001']+ 1;
            }
        }

        $svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="'.(($XXBreite + 4.5)*$vFactor +$vAbstandLinks).'" height="'.(390*$vFactor).'">';
        $svg .= '<line x1="'.(0 + $vAbstandLinks).'" y1="'.((5 + (359 - $yyHoehe) - 50)).'" x2="'.($XXBreite*$vFactor + 4.5*$vFactor + $vAbstandLinks).'" y2="'.((5 + (359 - $yyHoehe) - 50)).'" style="stroke:#ababab;stroke-width:1;" /><line x1="'.(0 + $vAbstandLinks).'" y1="'.((359 - $yyHoehe) - 5 - 50).'" x2="'.(0 + $vAbstandLinks).'" y2="'.(25 + (359 - $yyHoehe) - 50).'" style="stroke:#a5c614;stroke-width:2" />';
        $svg .= '<line x1="'.($XXBreite*$vFactor + 4.5*$vFactor + $vAbstandLinks-1).'" y1="'.((359 - $yyHoehe) - 5 - 50).'" x2="'.($XXBreite*$vFactor + 4.5*$vFactor + $vAbstandLinks-1).'" y2="'.(25 + (359 - $yyHoehe) - 50).'" style="stroke:#a5c614;stroke-width:2" />';
        $svg .= '<text x="'.(0 + $vAbstandLinks + (($XXBreite*$vFactor) / 2) - 30).'" y="'.((20 + (359 - $yyHoehe) - 70)).'" fill="#a5c614">'.((($XXBreite + 4.5))).' cm</text>';
        $svg .= '<text x="'.(20).'" y="'.((380 - $yyHoehe)*$vFactor-(($yyHoehe*$vFactor/2)*-1)-50).'" fill="#a5c614">'.((($yyHoehe))).' cm</text>';
        $svg .= '<line x1="'.(60).'" y1="'.((380 - $yyHoehe)*$vFactor-50).'" x2="'.(90).'" y2="'.((380 - $yyHoehe)*$vFactor-50).'" style="stroke:#a5c614;stroke-width:2" />';
        $svg .= '<line x1="'.(60).'" y1="'.((446)).'" x2="'.(90).'" y2="'.((446)).'" style="stroke:#a5c614;stroke-width:2" />';
        $svg .= '<line x1="'.(70).'" y1="'.((380 - $yyHoehe)*$vFactor-50).'" x2="'.(70).'" y2="'.(446).'" style="stroke:#ababab;stroke-width:1;" />';
        $svg .= $aStuetze;
        $svg .= $aStaender;
        $svg .= $aBoeden;
        $svg .= $aOrdner;
        $svg .= '</svg>';   
        $_POST['regal']['svg'] = $svg;
        $_POST['regal']['warenkorb'] = $wkorb;
        $_POST['regal']['gesamtBreite'] = $XXBreite + 4.5;
        $_POST['regal']['gesamtHoehe'] = $yyHoehe;
        $this->saveSvg($svg);
        $this->rAbstand['breite'] = ($vBreite * $vFactor + $vAbstandLinks + 5);
        $this->rAbstand['links'] = $vAbstandLinks;
        $this->rAbstand['factor'] = $vFactor;

        return $_POST['regal'];
    }

    private function saveSvg($svg) {

        $xsvg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
        $xsvg .= $svg;
        $verzeichnis = "bundles/the13thholzregal/konfig";
        if(!file_exists($verzeichnis)) {
            mkdir($verzeichnis, 0755);
        }
        $verzeichnis = "bundles/the13thholzregal/konfig/".date("Ym");
        if(!file_exists($verzeichnis)) {
            mkdir($verzeichnis, 0755);
        }
        file_put_contents($verzeichnis."/".$_SESSION['holzregal']['id'].".svg", $xsvg);
        $_SESSION['holzregal']['svg'] = $verzeichnis."/".$_SESSION['holzregal']['id'].".svg";
    }

    private function getArtikel() {
        $this->getBearerToken();
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_PORT => $_SERVER['SERVER_PORT'],
          CURLOPT_URL => "http://localhost:{$_SERVER['SERVER_PORT']}/api/product",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => [
            "Accept: application/vnd.api+json, application/json",
            "Authorization: Bearer ".$this->bearerToken
          ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          $res = json_decode($response, true);
          foreach($res['data'] AS $key => $value) {
            $d[$value['attributes']['productNumber']] = array(
                'bez' => $value['attributes']['name'], 
                'preis' => $value['attributes']['price']['0']['net'], 
                'articleID' => $value['id'], 
                'weight' => $value['attributes']['weight']);
          }
          return $d;
        }
    }

    private function getBearerToken() {

        $postParameter = array(
            'grant_type' => 'client_credentials',
            'client_id' => 'SWIAS1PHB3BMSLPMDUHNSKL5BA',
            'client_secret' => 'dDJZSko5NVF3Nm1ERHE3VTBIa3Y1NXR4ZE5vdkJHblhUbWs0RU8'
        );
        
        $curlHandle = curl_init($_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].'/api/oauth/token');
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, json_encode($postParameter));
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        
        $curlResponse = curl_exec($curlHandle);
        curl_close($curlHandle);
        $objResponse = json_decode($curlResponse);

        $this->bearerToken = $objResponse->access_token;

        return json_decode($curlResponse, true);

    }

}




/*

curl --request POST --url http://localhost:8000/api/oauth/token --header 'Content-Type: application/json' --data '{"grant_type":"client_credentials","client_id":"SWIAS1PHB3BMSLPMDUHNSKL5BA","client_secret":"dDJZSko5NVF3Nm1ERHE3VTBIa3Y1NXR4ZE5vdkJHblhUbWs0RU8"}'      


curl --request POST --url http://new.das-holzregal.de:443/api/oauth/token --header 'Content-Type: application/json' --data '{"grant_type":"client_credentials","client_id":"SWIADVM5NXP5D0HOA2L3OWW4BG","client_secret":"S01JQllRRUZNQWRvcmxyMXFRZE9Nb0duTURmblZMMkV6WnYzQ3U"}'      


curl --request POST --url http://localhost:8000/api/oauth/token --header 'Content-Type: application/json' --data '{"grant_type":"client_credentials","client_id":"SWIAS1PHB3BMSLPMDUHNSKL5BA","client_secret":"dDJZSko5NVF3Nm1ERHE3VTBIa3Y1NXR4ZE5vdkJHblhUbWs0RU8"}'


curl --request POST --url http://localhost:8000//store-api/context --header 'Content-Type: application/json' --data '{"sw-access-token":"SWSCM2L0WLFJBWFUDZR6VM1VDA"}'

*/