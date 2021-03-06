<?php
ini_set('display_errors', 1);
$mageFileName = getcwd() . '/app/Mage.php';
require $mageFileName;
Mage::app();
$base_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
$noImage = "catalog/product/no_selection";
$rootUrl = Mage::getBaseUrl() . 'media/catalog/product'; //Uso da URL base evita erros de rastreamento se utilizar CDN
#armazena atributos de produtos em uma coleção
$collection = Mage::getModel('catalog/product')
    ->getCollection()
    ->addAttributeToFilter('status', array('eq' => 1)) //Filtra produtos ativos
    ->addAttributeToFilter('image', array('notnull' => '', 'neq' => 'no_selection'))
    ->setStoreId(1);
#criaobjeto DOM
$dom = new DOMDocument("1.0", "UTF-8");
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
#Cria nó raiz com namespace
$root = $dom->createElement("urlset");
$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

#adiciona nós com dados das imagens
function addImage($document, $urlloc, $loc, $lastmod)
{
    #criar nó de produto
    $urlset = $document->createElement("url");
    #criar nó location
    $locElm = $document->createElement("loc", $urlloc);
    #ultima modficacao
    $lastmodElm = $document->createElement("lastmod", $lastmod);
    #frequencia de mudanca
    $cfElm = $document->createElement("changefreq", "daily");
    #prioridade
    $prioElm = $document->createElement("priority", "0.5");
    #Infos da imagem
    $imageElm = $document->createElement("image:image");
#Endereco da imagem
    $infImElm = $document->createElement("image:loc", $loc);

#Anexa nós ao raiz
    $urlset->appendChild($locElm);
    $urlset->appendChild($lastmodElm);
    $urlset->appendChild($cfElm);
    $urlset->appendChild($prioElm);
    $urlset->appendChild($imageElm);
    $imageElm->appendChild($infImElm);

    return $urlset;
}
foreach ($collection as $product) {
    $ImgLoc = $product->getProductUrl();
    $updatedAt = substr($product->getUpdatedAt(), 0, 10);
    $image = $rootUrl . $product->getImage();
    if ($image != ($base_url . $noImage)) {
        #utilizando a funcao para criar contatos adicionando no root
        $urlset = addImage($dom, $ImgLoc, $image, $updatedAt);
        $root->appendChild($urlset);
        $dom->appendChild($root);
    }
}

#salvando o arquivo
$dom->save("sitemap-image.xml");

#mostrar dados na tela
header("Content-Type: text/xml");
print $dom->saveXML();
