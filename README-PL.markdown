Informacje
===========

[![Build Status](https://secure.travis-ci.org/psliwa/PHPPdf.png?branch=master)](http://travis-ci.org/psliwa/PHPPdf)

Przykłady
=========

Przykłady dokumentów znajdują się w katalogu "examples". Plik "index.php" jest webowym interfejsem do otwierania przykładów, "cli.php" zaś jest interfejsem konsolowym. W webowym interfejsie przykłady są dostępne w formie dokumentu pdf oraz plików graficznych (wymagany Imagick).

Dokumentacja
============

Spis treści
-----------------

1. [Wstęp](#intro)
1. [Instalacja](#installation)
1. [Symfony2 bundle](#symfony2-bundle)
1. [FAQ](#faq)
1. [Parsowanie dokumentu i tworzenie pdf'a](#parsing)
1. [Podstawowa struktura dokumentu](#structure)
1. [Dziedziczenie](#inheritance)
1. [Struktura stylów](#stylesheet)
1. [Paleta kolorów](#color-palette)
1. [Standardowe tagi](#tags)
1. [Atrybut](#attributes)
1. [Atrybuty złożone](#complex-attributes)
1. [Jednostki](#units)
1. [Kody kreskowe] (#barcodes)
1. [Wykresy] (#charts)
1. [Hiperlinki](#hyperlinks)
1. [Zakładki](#bookmarks)
1. [Notatki](#notes)
1. [Powtarzalne nagłówki i stopki](#headers)
1. [Znaki wodne](#watermarks)
1. [Numerowanie stron](#page-numbering)
1. [Wykorzystanie istniejącego dokumentu jako szablon](#templates)
1. [Podział strony na kolumny](#columns)
1. [Łamanie stron i kolumn](#page-break)
1. [Metadane](#metadata)
1. [Konfiguracja](#configuration)
1. [Markdown - wsparcie](#markdown)
1. [Silnik do generowania obrazków] (#image-generation)
1. [Znane ograniczenia](#limitations)
1. [TODO - czyli plany](#todo)
1. [Wymagania techniczne](#requirements)

<a name="intro"></a>
Wprowadzenie
----------

PHPPdf jest biblioteką, która zamienia dokument xml w dokument pdf lub też pliki graficzne. Dokument źródłowy xml jest podobny do html'a, ale jest sporo różnic w nazwach i właściwościach atrybutów, właściwości tagów, jest wiele niestandardowych tagów, nie wszystkie tagi z HTML'a są wspierane, arkusz styli jest opisywany w dokumencie xml, a nie css. Założeniem tej biblioteki nie jest transformacja HTML -> PDF / JPEG / PNG, a XML -> PDF / JPEG / PNG. Nazwy tagów i niektóre nazwy atrybutów są takie same jak w HTML'u, aby zmniejszyć krzywą uczenia się tej biblioteki.

<a name="installation"></a>
Instalacja
----------

PHPPdf jest dostępne na packagist.org, więc możesz użyć narzędzia composer aby ściągnąć tą bibliotekę ze wszystkimi zależnościami. PHPPdf wymaga aby opcja "minimum-stability" była ustawiona na dev.

```
"minimum-stability": "dev"
```

Jeśli nie chcesz używać narzędzia composer, poniżej znajdziesz instrukcje jak ręcznie zainstalować tą bibliotekę wraz ze wszystkimi zależnościami.

Biblioteka posiada zależności do zewnętrznych bibliotek: 

* php-markdown
* ZendPdf
* Zend_Memory (Zend Framework w wersji 2.0.x)
* Zend_Cache (Zend Framework w wersji 2.0.x)
* Zend_Stdlib (Zend Framework w wersji 2.0.x)
* Zend_EventManager (Zend Framework w wersji 2.0.x)
* Zend_ServiceManager (Zend Framework w wersji 2.0.x)
* Zend_Barcode (Zend Framework w wersji 2.0.x)
* Imagine

Aby biblioteka była gotowa do użytku, trzeba pobrać te zależności. Należy wywołać z wysokości głównego katalogu biblioteki polecenie (należy mieć zainstalowanego klienta git):

```bash
    php vendors.php
```

Alternatywnie zależności można umieścić ręcznie w katalogu "lib/vendor". Domyślnie plik vendors.php **pobierze całą bibliotekę ZF2**, pamietaj że **konieczne do działania są tylko paczki ZendPdf, Zend_Memory, Zend_Cache, Zend_Stdlib, Zend_EventManager oraz Zend_ServiceManager**. Do obsługi kodów kreskowych wymagane jest **Zend_Barcode**. **Resztę paczek i plików ZF2 możesz usunąć**.
    
<a name="symfony2-bundle"></a>
Symfony2 bundle
----------------

Tutaj znajduje się [Symfony2 bundle][1] integrujący tą bibliotekę z Symfony2.

<a name="faq"></a>
FAQ
----------------

**Mam krzaki zamiast polskich znaków, co zrobić?**

Należy ustawić czcionkę, która wspiera kodowanie utf-8 z zakresu polskich znaków. PHPPdf ma dołączonych kilka takich czcionek, np. DejaVuSans, czy Kurier. W przykładzie "font" jest pokazane w jaki sposób ustawić rodzaj czcionki z wysokości szablonu stylów.
Możesz dodać dowolne czcionki, aby to osiągnąć powinieneś przygotować plik konfiguracyjny w formacie xml oraz skonfigurować obiekt Facade, tak jak w poniższym przykładzie:

```xml
    //kod xml
    <fonts>   
        <font name="DejaVuSans">
       	    <normal src="%resources%/fonts/DejaVuSans/normal.ttf" /><!-- "%resources%" zostanie zastąpione ścieżką do katalogu PHPPdf/Resources -->
            <bold src="%resources%/fonts/DejaVuSans/bold.ttf" />
            <italic src="%resources%/fonts/DejaVuSans/oblique.ttf" />
            <bold-italic src="%resources%/fonts/DejaVuSans/bold+oblique.ttf" />
        </font>
    </fonts>
```

```php    
    //kod php
    $loader = new PHPPdf\Core\Configuration\LoaderImpl();
    $loader->setFontFile(/* path to fonts configuration file */);
    $builder = PHPPdf\Core\FacadeBuilder::create($loader);
    $facade = $builder->build();
```

Więcej szczegółów możesz znaleść w rozdziale [Konfiguracja](#configuration).


**Bardzo długo trwa generowanie prostego dokumentu z obrazkiem w formacie png, co zrobić?**

PHPPdf wykorzystuje bibliotekę Zend_Pdf, która słabo sobie radzi w parsowaniu plików png bez kompresji. Skompresuj pliki png.

**Jak mogę zmienić rozmiar/orientację strony?**

Do ustawiania rozmiarów strony służy atrybut "page-size" tagów page oraz dynamic-page. Wartość tego atrybutu ma składnię "szerokość:wysokość". Dozwolone są również predefiniowane wartości: a4, a4-landscape, letter oraz letter-landscape.

Przykład:

```xml
    <page page-size="100:50">text</page>
    <page page-size="a4">text</page>
    <page page-size="letter-landscape">text</page>
```

<a name="parsing"></a>
Parsowanie dokumentu i tworzenie pdf'a.
---------------------------------------

Najprostrzy sposób wykorzystania biblioteki:

```php
    //zarejestrowanie autoloadera PHPPdf oraz vendor (Zend_Pdf i inne zależności)
    require_once 'PHPPdf/Autoloader.php';
    PHPPdf\Autoloader::register();
    PHPPdf\Autoloader::register('sciezka/do/biblioteki/lib/vendor/Zend/library');
    
    //jeśli chcesz generować pliki graficzne
    PHPPdf\Autoloader::register('sciezka/do/biblioteki/lib/vendor/Imagine/lib');

    $facade = new PHPPdf\Core\Facade(new PHPPdf\Core\Configuration\Loader());

    //$documentXml i $stylesheetXml to ciągi znaków zawierające dokumenty XML, $stylesheetXml jest opcjonalne
    $content = $facade->render($documentXml, $stylesheetXml);

    header('Content-Type: application/pdf');
    echo $content;
```

<a name="structure"></a>
Podstawowa struktura dokumentu.
-------------------------------

Biblioteka bazuje na formacie XML przypominającym HTML, ale w żadnym wypadku nie jest to HTML - niektóre tagi się różnią, interpretacja niektórych atrybutów jest inna niż w standardzie HTML i CSS, sposób dodawania atrybutów również jest inny. Najprostrzy dokument ma następującą strukturę:

```xml
    <pdf>
        <dynamic-page>
            <h1>Nagłówek</h1>
            <p>paragraf</p>
            <div color="red">Warstwa</div>
            <table>
                <tr>
                    <td>kolumna</td>
                    <td>kolumna</td>
                </tr>
            </table>
        </dynamic-page>
    </pdf>
```
    
Zalecane jest dodawanie następującej deklaracji DOCTYPE do dokumentów, pozwala ona na zaminę encji na wartości:

```xml
    <!DOCTYPE pdf SYSTEM "%resources%/dtd/doctype.dtd">
```

Korzeń dokumentu musi się nazywać "pdf". Element "dynamic-page" jest stroną, która się dynamicznie dzieli gdy zostanie przepełniona. Alternatywą jest tag "page", czyli pojedyńcza niepodzielna strona. Są różnice w nadawaniu atrybutom wartości w stosunku do HTML. Aby nadać obramowanie i tło warstwie należy posłużyć się atrybutem złożonym "border" oraz "background", atrybuty te mają swoje własne właściwości, np. kolor, rozmiar, zaokrąglenie. Alternatywną składnią do ustawiania atrybutów oraz atrybutów złożonych (complex-attribute) jest element "stylesheet". Przykład:

```xml
    <pdf>
        <dynamic-page>
            <div color="red" border.color="black" background.color="pink">
                Ten tekst jest czerwony na różowym tle w czarnym obramowaniu
            </div>
        </dynamic-page>
    </pdf>
```
    
Alternatywna składnia (element stylesheet):

```xml
    <pdf>
        <dynamic-page>
            <div>
                <stylesheet>
                    <attribute color="red" />
                    <complex-attribute name="border" color="black" />
                    <complex-attribute name="background" color="pink" />
                </stylesheet>
                Ten tekst jest czerwony na różowym tle w czarnym obramowaniu
            </div>
        </dynamic-page>
    </pdf>
```

Atrybuty można nadawać za pomocą atrybutów XML bezpośrednio po nazwie tagu lub też za pomocą wspomnianego tagu "stylesheet". Nie istnieje atrybut "style" znany z HTML'a.

Biblioteka jest bardzo rygorystyczna pod względem poprawności tagów i atrybutów. Jeśli zostanie użyty nieistniejący tag lub atrybut, dokument się nie sparsuje - zostanie wyrzucony wyjątek z odpowiednią treścią.

<a name="inheritance"></a>
Dziedziczenie
--------------

Atrybut "id" ma całkowicie inne znaczenie niż w HTML'u. Atrybut "name" jest aliasem do "id". Id musi być unikalne w obrębie dokumentu, w przeciwnym wypadku wystąpi błąd parsowania. Służy on m. in. do identyfikowania elementów przy dziedziczeniu. Przykład:

```xml
    <pdf>
        <dynamic-page>
            <div id="warstwa-1" color="red" font-type="judson" font-size="16px">
                <stylesheet>
                    <complex-attribute name="border" color="green" />
                </stylesheet>
                Warstwa 1
            </div>
            <div extends="warstwa-1">
                Warstwa 2 dziedzicząca style (typ, atrybuty proste i złożone) po warstwie 1)
            </div>
        </dynamic-page>
    </pdf>
```

Druga warstwa dziedziczy wszstkie atrybuty proste i złożone po pierwszej, nawet te które zostały nadane z zewnętrznego arkuszu stylów.

Priorytety nadawania wartości atrybutom:

1. Tag stylesheet wewnątrz elementu
2. Zdefiniowanie atrybutów bezpośrednio po nazwie tagu
3. Atrybuty pobrane z arkusza stylów
4. Atrybuty odziedziczone po innym elemencie

Przykład:

```xml
    <pdf>
        <page>
            <div id="1" color="#cccccc" height="100px" text-align="right">
            </div>
            <div extends="1" color="#aaaaaa" height="150px">
                <stylesheet>
                    <attribute name="height" value="200px" />
                </stylesheet>
            </div>
        </page>
    </pdf>
```

Drugi "div" będzie miał następujące atrybuty:

- text-align: right
- color: #aaaaaa
- height: 200px

<a name="stylesheet"></a>
Struktura arkusza stylów.
-------------------------

Arkusze stylów muszą się znajdować w osobnym pliku, krótki (jako atrybut xml) oraz długi (jako osobne tagi) sposób definicji stylów jest wspierany. Składnia arkusza stylów:

Krótki sposób:

```xml
    <stylesheet>
        <!-- style są wbudowane w tag jako atrybuty xml, atrybut "class" ma takie samo znaczenie co w HTML/CSS -->
        <div class="class" font-size="12px" color="gray" background.color="yellow">
            <!-- element zagnieżdżony, odpowiednik selektora z CSS: "div.class p" -->
            <p margin="10px 15px">
            </p>
        </div>

        <!-- odpowiednik selektora z CSS: ".another-class", tag "any" jest wildcardem (każdy tag do niego pasuje) -->
        <any class="another-class" text-align="right">
        </any>

        <h2 class="header">
            <span font-size="9px">
            </span>
            
            <div font-style="bold">
            </div>
        </h2>
    </stylesheet>
```

Długi sposób:

```xml
    <stylesheet>
        <div class="klasa">
            <!-- atrybuty proste i złożone zagnieżdzone w ścieżce selektora div.klasa -->
            <attribute name="font-size" value="12px" />
            <attribute name="color" value="grey" />
            <!-- odpowiednik atrybutu background.color -->
            <complex-attribute name="background" color="yellow" />

            <!-- kolejny element, odpowiadająca składnia selektora z CSS: "div.klasa p" -->
            <p>
                <attribute name="margin" value="10px 15px" />
            </p>
        </div>

        <!-- odpowiednik selektora ".inna-klasa" z CSS, tag "any" oznacza dowolny tag -->
        <any class="inna-klasa">
            <attribute name="text-align" value="right" />
        </any>

        <h2 class="naglowek">
            <span>
                <attribute name="font-size" value="9px" />
            </span>
            <div>
                <attribute name="font-style" value="bold" />
            </div>
        </h2>
    </stylesheet>
```

<a name="color-palette"></a>
Palety kolorów
----------------

Można definiować również paletę kolorów, czyli mapę nazw logicznych na konkretne kolory. Paleta kolorów daje możliwość tworzenia nowych lub nadpisywania domyślnych nazwanych kolorów. Domyślnie PHPPdf obsługuje nazwane kolory wg standardu W3C (np. "black" = "#000000"). Paleta kolorów może mieć zastosowanie do zachowania zasady DRY, gdyż informacje o kolorach użytych w dokumentach chcemy przechowywać tylko w jednym miejscu. Można też generować dany dokument używając kilku palet.

Przykład:

```xml
    <!-- plik colors.xml -->
    <colors>
        <color name="header-color" hex="#333333" />
        <color name="line-color" hex="#eeeeee" />
    </colors>
    
    <!-- plik stylesheet.xml -->
    <h2 color="header-color" />
    <hr background-color="line-color" />
    <table>
        <td border-color="line-color" />
    </table>
    
    <!-- plik document.xml -->
    <pdf>
        <page>
            <h2>Nagłówek</h2>
            <hr />
            <table>
                <tr>
                    <td>Dane</td>
                    <td>Dane</td>
                </tr>
            </table>
        </page>
    </pdf>
```
    
```php
    //kod php
    use PHPPdf\DataSource\DataSource;
    $facade = ...;
    
    $content = $facade->render(DataSource::fromFile(__DIR__.'/document.xml'), DataSource::fromFile(__DIR__.'/stylesheet.xml'), DataSource::fromFile(__DIR__.'/colors.xml'));
```

<a name="tags"></a>
Standardowe tagi
-----------------

Biblioteka obsługuje podstawowe tagi zaczerpnięte z języka HTML: div, p, table, tr, td, b, strong, span, a, h1, h2, h3, h4, h5, img, br, ul, li
Ponadto obsługiwane są niestandardowe tagi:

* dynamic-page - strona, która się dynamicznie dzieli gdy zostaje przepełniona
* page - pojedyncza strona z ustalonym rozmiarem
* elastic-page - pojedyncza strona, która dostosowuje swoją wysokość w zależności od elementów podrzędnych (podobnie jak pozostałe tagi). Dla tego tagu nie działają: nagłówek, stopka, watermark oraz atrybut template-document. Tag przydatny zwłaszcza gdy generujemy pliki graficzne (silnik image).
* page-break, column-break, break - złamanie strony lub kolumny, jest to element podrzędny dynamic-page lub column-layout, czyli musi być bezpośrednim dzieckiem tego elemntu! Te trzy tagi są aliasami.
* column-layout - podział obszaru roboczego na kolumny, dodatkowe atrybuty: number-of-columns oraz margin-between-columns
* barcode - kod kreskowy, więcej informacji w rozdziale <a href="#barcodes">kody kreskowe</a>
* circle - element którego obramowanie oraz tło jest w kształcie koła. Dodatkowe atrybuty: radius (nadpisuje szerokość oraz wysokość)
* pie-chart - element który może być wykorzystany do narysowania prostego wykresu kołowego (więcej informacji w rozdziale <a href="#charts">wykresy</a>)


Istnieją tagi, które służą jedynie do określania wartości atrybutów, zbioru atrybutów lub zbioru elementów:

* stylesheet - style dla elementu nadrzędnego
* attribute - atrybut, bezpośredni element podrzędny dla "stylesheet". Wymagane atrybute tego elementu: name - nazwa atrybutu, value - wartość atrybutu
* complex-attribute - atrybut złożony, bezpośredni element podrzędny dla "stylesheet". Wymagany atrybut tego elementu: name - nazwa.
* placeholders - definiuje wartości "slotów" dla elementu podrzędnego. Elementy podrzędne "placeholders" są specyficzne dla tagu nadrzędnego. Ten tag powinien być pierwszym tagiem w rodzicu. 
* metadata - definiuje dane meta dla dokumentu pdf, bezpośredni element podrzędny korzenia dokumentu
* behaviours - definiuje zachowania dla elementu nadrzędnego. Obsługiwane zachowania: ref, href, bookmark, note (działanie takie samo jak dla atrybutów o tych samych nazwach)

<a name="attributes"></a>
Atrybuty
---------

* width oraz height: ustawienie wysokości i szerokości na sztywno, wspierane jednostki miary są opisane w osobnym [rozdziale](#units). Jest możliwe użycie wartości relatywnych wyrażonych w procentach
* max-width oraz max-height: ustawia maksymalną szerokość i wysokość
* margin (margin-top, margin-bottom, margin-left, margin-right): margines podobny jak w HTML/CSS z taką różnicą, że marginesy sąsiadów się sumują. Dla marginesów bocznym możliwa jest wartość "auto" - działa podobnie jak w HTML/CSS
* padding (padding-top, padding-bottom, padding-left, padding-right): dopełnienie wewnętrzne - tak jak w HTML/CSS
* font-type - typ czcionki. Nazwa czcionki musi występować w pliku konfiguracyjnym fonts.xml, w przeciwnym wypadku zostanie wyrzucony wyjątek
* font-size - rozmiar czcionki w punktach, brak obsługi jednostek wielkości
* font-style - styl czcionki, dozwolone wartości: normal, bold, italic, bold-italic
* color - kolor tekstu. Przyjmowane wartości takie jak w HTML/CSS
* breakable - określa czy element może zostać podzielony na dwie strony, dla większości elementów domyślna wartość to true
* float - działanie podobne, aczkolwiek nie takie same jak w HTML/CSS. Wartości left|none|right, domyślnie none
* line-height - działanie takie jak w HTML/CSS. Domyślna wartość to 1.2*font-size
* text-align - działanie takie jak w HTML/CSS. Wartości left|center|right|justify, domyślnie left.
* text-decoration - dozwolone wartości: none, underline, overline, line-through
* break - łamie stronę lub kolumnę jeśli element jest bezpośrednim dzieckiem elementu dynamic-page lub column-layout
* colspan, rowspan - działanie analogiczne do atrybutów html (rowspan jeszcze nie jest zaimplementowane)
* href - zewnętrzy adres url, gdzie element powinien linkować
* ref - id elementu do którego posiadacz tego atrybutu powinien linkować (odpowiednik kotwic w HTML'u)
* bookmark - tworzy zakładkę o podanej nazwie linkującą do tego tagu
* note - tworzy notatkę dla danego elementu o podanej treści
* dump - dozwolone wartości: true or false. Tworzy notatkę z informacjami przeznaczonymi do debugowania, np. wartości atrybutów, pozycja itp.
* rotate - kąt obrotu elementu. Obsługa tego atrybutu nie jest w pełni zaimplementowana, działa poprawnie ze znakami wodnymi (patrz sekcja "Znaki wodne"). Możliwe wartości: XXdeg (w stopniach), XX (w radianach), diagonally, -diagonally.
* alpha - możliwe wartości: od 0 do 1. Przeźroczystość elementu i jego dzieci.
* line-break - złamanie linii (true lub false), domyślnie ustawione na true tylko dla tagu "br"
* style - to samo co w html'u, ten atrybut służy do ustawiania wartości innym atrybutom, np.: style="width: 100px; height: 200px; margin: 20px 0;". Każdy atrybut musi być zakonczony średnikiem (";"), nawet ostatni.
* ignore-error (dla tagu img) - ignorować błąd ładowania pliku, czy wyrzucać wyjątek? Domyślnie false, czyli jest wyrzucany wyjątek.
* keep-ratio (dla tagu img) - utrzymuje proporcje obrazka wycinając fragment obrazka, nawet gdy zadane rozmiary nie są w proporcjach oryginalnego obrazka. Domyślnie false.
* position - to samo co w htmlu. Dozwolone wartości: static (domyślnie), relative, absolute
* left oraz right - to samo co w htmlu, działa z position relative lub absolute. Atrybuty right oraz bottom nie są wspierane.

<a name="complex-attributes"></a>
Atrybuty złożone
----------------

Atrybuty złożone mogą zostać ustawione za pomocą notacji "nazwaAtrybutu.nazwaWłaściwości" lub "nazwaAtrybutu-nazwaWłaściwości" (np. border.color="black" lub border-color="black").

* border:
    - color: kolor obramowania
    - style: styl obramowania, możliwe wartości: solid (linia ciągła), dotted (predefiniowana linia przerywana) lub własna definicja w postaci ciągu liczb oddzielonych spacjami
    - type: które krawędzie mają mieć obramowanie - domyślnie "top+bottom+left+right", czyli każda krawędź. Możliwa wartość none (wyłączenie obramowania)
    - size: rozmiar obramowania
    - radius: zaokrąglenie rogów w jednostkach długości (uwaga: przy ustawionym tym parametrze ignorowany jest parametr typu - zawsze obramowanie z zaokrąglonymi rogami jest pełne)
    - position: przesunięcie obramowania względem orginalnego położenia. Wartości dodatnie rozszerzają obramowanie, wartości ujemnie zwężają. Dzięki manipulacji tym parametrem i dodaniu kilku obramowań, można uzyskać złożone i skomplikowane obramowania.

* background:
    - color: kolor tła
    - image: obrazek tła
    - repeat: sposób powtarzania obrazka (none|x|y|all)
    - radius: zaokrąglanie rogów tła w jednostkach długości (w chwili obecnej działa tylko dla koloru, nie obrazka)
    - use-real-dimension: atrybut wykorzystany jak narazie dla tagu page (lub dynamic-page). True jeśli zapełniać marginesy, false w przeciwnym wypadku.
    - image-width: szerokość obrazka tła, może być wartością procentową
    - image-height: wysokość obrazka tła, może być wartością procentową
    - position-x: horyzontalna pozycja obrazka tła, dozwolone wartości: left, center, right lub wartość liczbowa (domyślnie: left)
    - position-y: wertykalna pozycja obrazka tła, dozwolone wartości: top, center, bottom lub wartość liczbowa (domyślnie: top)

Można dodawać kilka upiększeń tego samego typu (np. 3 różne obramowania) używając tagu "stylesheet" zamiast krótkiej notacji ("border.color"):

```xml
    <pdf>
        <dynamic-page>
            <div>
                <stylesheet>
                    <!-- Górna i dolna krawędź czerwona, boczne żółto-szare -->
                    <complex-attribute name="border" color="red" type="top+bottom" />
                    <complex-attribute id="borderLeftAndRight" name="border" color="yellow" type="left+right" size="4px" />
                    <complex-attribute id="outerBorderLeftAndRight" name="border" color="gray" type="left+right" size="2px" position="1px" />
                </stylesheet>
            </div>
        </dynamic-page>
    </pdf>
```

W tym przykładzie drugie obramowanie ma identyfikator "borderLeftAndRight", jakby go nie było to atrybuty drugiego obramowania zostały by złączone z atrybutami z pierwszego obramowania. Domyślny identyfikator "id" jest równy atrybutowi "name". Identyfikatory "id" dla atrybutów złożonych (complex-attributes) nie mają nic wspólnego z atrybutami "id" dla elementów (nodeów). Można tworzyć obramowania złożone manipulując pozycją, tak jak w powyższym przykładzie (outerBorderLeftAndRight).

<a name="units"></a>
Jednostki
----------------

Obsługiwane jednostki dla numerycznych atrybutów: in (cal), cm (centymetr), mm (milimetr), pt (punkt), pc (pica), px (pixel), % (procent - tylko dla width i height).

Obecnie nieobslugiwane jednostki: em, ex

Gdy jednostka zostanie pominięta (na przykład: font-size="10"), to jednostką jest punkt (pt). 1pt = 1/72 cala

<a name="barcodes"></a>
Kody kreskowe
----------------

Kody kreskowe są obsługiwane za pomocą tagu &lt;barcode&gt;. PHPPdf do generowania kodów korzysta z biblioteki Zend\Barcode. 

Przykład:

```xml
    <pdf>
        <dynamic-page>
            <barcode type="code128" code="PHPPdf" />
        </dynamic-page>
    </pdf>
```

Tag &lt;barcode&gt; obsługuje większość standardowych atrybutów oraz ma szereg innych atrybutów:

* type - typ kodu kreskowego, dozwolone wartości: code128, code25, code25interleaved, code39, ean13, ean2, ean5, ean8, identcode, itf14, leitcode, planet, postnet, royalmail, upca, upce
* draw-code - odpowiednik opcji drawCode z Zend\Barcode
* bar-height - odpowiednik opcji barheight z Zend\Barcode
* with-checksum - odpowiednik opcji withChecksum z Zend\Barcode
* with-checksum-in-text - odpowienik opcji withChecksumInText z Zend\Barcode
* bar-thin-width - odpowiednik opcji barThinWidth z Zend\Barcode
* bar-thick-width - odpowiednik opcji barThickWidth z Zend\Barcode
* rotate - odpowiednik opcji orientation z Zend\Barcode

Opis poszczególnych opcji oraz wartości domyślne można znaleźć w [dokumentacji Zend\Barcode][3]

Do wyświetlania tekstowego kodów kreskowych nie można użyć wbudowanych czcionek pdf: courier, times-roman oraz helvetica. Zostanie to niebawem poprawione.

<a name="charts"></a>
Charts
----------------

PHPPdf wspiera rysowanie prostych wykresów. Obecnie jest obsługiwany tylko prosty wykres kołowy.

Przykład:

```xml
    <pdf>
        <dynamic-page>
            <pie-chart radius="200px" chart-values="10|20|30|40" chart-colors="black|red|green|blue"></pie-chart>
        </dynamic-page>
    </pdf>
```
    
Tag pie-chart ma trzy dodatkowe atrybuty:

* radius - promien wykresu
* chart-values - wartości wykresu, nie muszą się sumować do 100. Każda wartość powinna być oddzielona znakiem "|"
* chart-colors - kolory wszystkich wartości. Każdy kolor powinnien być oddzielony znakiem "|"

<a name="hyperlinks"></a>
Hiperlinki
----------

Biblioteka wspiera wewnętrzne oraz zewnętrzne hiperłącza. Zewnętrzne hiperłącza linkują do adresów url, wewnętrzne zaś do innego tagu wewnątrz dokumentu.

Przykład:

```xml
    <pdf>
        <dynamic-page>
            <a href="http://google.com">idź do google.com</a>
            <br />
            <a ref="some-id">idź do innego tagu</a>
            <a href="#some-id">go to another tag</a> <!-- anchor style ref -->
            <page-break />
            <p id="some-id">Tak, to jest inny tag! ;)</p>
        </dynamic-page>
    </pdf>
```

Każdy element ma attrybuty "href" oraz "ref", nawet div. Nie możesz zagnieżdżać elementów wewnątrz tagu "a". Jeśli chcesz użyć np. img jako linka, powinieneś wykorzystać do tego atrybut "href" (zewnętrzny link) lub "ref" (wewnętrzny link) bezpośrednio w tagu "img".

<a name="bookmarks"></a>
Zakładki
--------

Preferowany sposób tworzenia zakładek jest tag "behaviours". Ten sposób nie ogranicza struktury dokumentu, właściciel zakładki-rodzica nie musi być rodzicem zakładki-dziecka.

Przykład:

```xml
    <pdf>
	    <dynamic-page>
		    <div>
		        <behaviours>
		            <bookmark id="1">bookmark rodzica</bookmark>
		        </behaviours>
		        Jakaś treść
		    </div>
		    <div>
		        <behaviours>
		            <bookmark parentId="1">bookmark dziecka</bookmark>
		        </behaviours>
		        Inna treść
		    </div>
		    <div>
		        <behaviours>
		            <bookmark parentId="1">inny bookmark dziecka</bookmark>
		        </behaviours>
		        Inna treść
		    </div>
		    <div>
		        <behaviours>
		            <bookmark>inny bookmark rodzica</bookmark>
		        </behaviours>
		       Jakaś treść
		    </div>
		</dynamic-page>
    </pdf>
```

Skrótem dla tagu "behaviours" jest atrybut "bookmark", jeśli przypiszesz mu jakąś wartość to zostanie utworzona zakładka, która linkuje do tego tagu. Zakładka tagu rodzica jest również rodzicem zakładek dzieci tego tagu.

Przykład:

```xml
    <pdf>
	    <dynamic-page>
		    <div bookmark="bookmark rodzica">
		        Jakaś treść
		        <div bookmark="bookmark dziecka">
		            Inna treść
		        </div>
		        <div bookmark="inny bookmark dziecka">
		            Inna treść
		        </div>
		    </div>
		    <div bookmark="inny bookmark rodzica">
		       Jakaś treść
		    </div>
		</dynamic-page>
    </pdf>
```

Powyższe struktury (obydwa przykłady) utworzą poniższą strukturę zakładek:

* bookmark rodzica
    - bookmark dziecka
    - inny bookmark dziecka
* inny bookmark rodzica

<a name="notes"></a>
Notatki
-------

Notatka może zostać dodana poprzez atrybut "note".

Przykład:

```xml
    <pdf>
        <dynamic-page>
            <div note="treść notatki"></div>
        </dynamic-page>
    </pdf>
```

Parser xml normalizuje wartości atrybutów, czego skutkiem jest ignorowanie znaków nowej linii. Jeśli chcesz dodać notatkę, w której znaki nowej lini są ważne, możesz skorzystać ze składni:

```xml
    <pdf>
        <dynamic-page>
            <div>
                <behaviours>
                    <note>Tekst notatki</note>
                </behaviours>
            </div>
        </dynamic-page>
    </pdf>
```

<a name="headers"></a>
Powtarzalne nagłówki i stopki
------------------------------

Aby dodać powtarzalny nagłówek i/bądź stopkę należy wykorzystać tag "placeholders". Niektóre elementy mają specjalne "sloty": strona ma nagłówek i stopkę, tabela może mieć nagłówek (TODO: jeszcze nie zaimplementowane) itp.

```xml
    <pdf>
        <dynamic-page>
            <placeholders>
                <header>
                    <div height="50px" width="100%">
                        Nagłówek
                    </div>
                </header>
                <footer>
                    <div height="50px" width="100%">
                        Stopka
                    </div>
                </footer>
            </placeholders>
        </dynamic-page>
    </pdf>
```

Nagłówek i stopka muszą mieć bezpośrednio określoną wysokość. Wysokość ta sumuje się z marginesami górnym i dolnym, czyli rozmiar roboczy strony jest to rozmiar pomniejszony o marginesy górny i dolny oraz o wysokość stopki i nagłówka.

<a name="watermarks"></a>
Znaki wodne
-----------

Strona ma placeholder o nazwie "watermark". Jako znak wodny można użyć element blokowy, który może zawierać dzieci, np. div, p, h1 (ale nie span, tekst czy img). Jeśli chcesz użyć tagu img lub tekstu jako znak wody, powinieneś ten element opakować np. w tag div.

Przykład:

```xml
    <pdf>
        <dynamic-page>
            <placeholders>
                <watermark>
                    <!-- jako "rotate" możesz użyć absolutnych wartości (45deg - w stopniach, 0.123 - w radianach) lub relatywnych wartości ("diagonally" oraz "-diagonally" - kąt pomiędzy przekątną, a podstawą strony) -->
                    <div rotate="diagonally" alpha="0.1">
                        <img src="ścieżka/do/zdjęcia.png" />
                    </div>
                </watermark>
            </placeholders>
        </dynamic-page>
    </pdf>
```

<a name="page-numbering"></a>
Numerowanie stron
--------------

Istnieją dwa tagi, które można wykorzystać do pokazania informacji o stronie w obrębie obecnego elementu "dynamic-page" (nie łączną liczbę stron w całym dokumencie!) w stopce, nagłówku lub znaku wodnym: page-info oraz page-number. Page-info pokazuje obecny numer strony oraz liczbę wszystkich stron, page-number pokazuje tylko obecny number strony.

Atrybuty tych tagów:

* format - format wynikowego ciągu znaków który będzie wykorzystany jako argument funkcji sprintf. Domyślne wartości: "%s." dla page-number, "%s / %s" dla page-info.
* offset - wartość, która będzie dodatan do obecnego numeru strony oraz liczby wszystkich stron. Użyteczne jeśli chcesz liczyć strony od innej wartości niż zero. Domyślnie: 0.

Przykład:

```xml
    <pdf>
        <dynamic-page>
            <placeholders>
                <header>
                    <div height="20px">
                        <page-info format="strona %s na %s" />

                        <!-- gdy chcemy numerować np. od 2 -->
                        <page-info offset="1" format="strona %s na %s" />

                        <!-- gdy chcemy wyświetlić tylko numer strony -->
                        <page-info format="%1$s." />
                        <!-- lub -->
                        <page-number />

                        <!-- gdy chcemy wyświetlić całkowitą liczbę stron -->
                        <page-info format="%2$s stron" />
                    </div>
                </header>
            </placeholders>
            Jakiś tekst
        </dynamic-page>
    </pdf>
```

<a name="templates"></a>
Wykorzystanie istniejącego dokumentu jako szablon
-------------------------------------------------

Tag "page" oraz "dynamic-page" posiadają atrybut "document-template", który pozwala na wskazanie pliku z dokumentem, który ma być wykorzystany jako szablon. Dla tagu "page" szablonem strony będzie pierwsza strona wskazanego dokumentu, zaś dla tagu "dynamic-page" szablonami kolejnych stron, będą odpowiednie, kolejne strony wskazanego dokumentu.

Przykład:

```xml
    <pdf>
        <dynamic-page document-template="sciezka/do/pliku.pdf">
            <div>Jakaś treść</div>
        </dynamic-page>
    </pdf>
```

<a name="columns"></a>
Podział strony na kolumny.
--------------------------

Strona może być podzielona na kolumny.

```xml
    <pdf>
        <dynamic-page>
            <column-layout>
                <div width="100%" height="2500px" background.color="green">
                </div>
            </column-layout>
        </dynamic-page>
    </pdf>
```

Powyższy xml określa kilka stron dokumentu pdf z zielonymi prostokątami podzielonymi na 2 kolumny. Tag "column-layout" ma dwa dodatkowe atrybuty: number-of-columns oraz margin-between-columns. Domyślna wartość tych atrybutów to odpowiednio 2 oraz 10.

<a name="page-break"></a>
Łamanie stron i kolumn
----------------------

Strona i kolumna może być ręcznie złamana przez jeden z tagów: page-break, column-break, break. Każdy z tych tagów ma dokładnie takie samo znaczenie. Te tagi muszą być bezpośrednimi dziećmi łamanego elementu (tagów dynamic-page lub column-layout).

Jeśli chcesz uniknąć automatycznego łamania strony lub kolumny dla szczególnego tagu, powinieneś wyłączyć atrybut "breakable" dla tego tagu.

Przykład:

```xml
    <pdf>
        <dynamic-page>
            <div breakable="false">ten div nie będzie automatycznie łamany</div>
        </dynamic-page>
    </pdf>
```

<a name="metadata"></a>
Metadane
--------

Metadane mogą zostać dodane za pomocą atrybutów korzenia dokumentu. Metadane które można ustawić to: Creator, Keywords, Subject, Author, Title, ModDate, CreationDate oraz Trapped. Nazwy tych atrybutów są wrażliwe na wielkość znaków.

Przykład:

```xml
    <pdf Author="Piotr Śliwa" Title="Dokument testowy">
        <!-- jakieś inne elementy -->
    </pdf>
```

<a name="configuration"></a>
Konfiguracja
-------------

Biblioteka ma 4 podstawowe pliki konfiguracyjne, które pozwalają na dostosowanie biblioteki do swoich potrzeb oraz do jej rozszerzenia.

* complex-attributes.xml - przypisywanie klas upiększeń (atrybutów złożonych) pod nazwy logiczne, które identyfikują dany typ atrybutu złożonego w obrębie całej biblioteki
* nodes.xml - definiowanie tagów dostępnych w dokumencie xml wraz z domyślnymi stylami oraz obiektami formatującymi
* fonts.xml - definowanie czcionek i przypisywanie ich do nazw logicznych, które identyfikują daną czcionkę w obrębie całej biblioteki
* colors.xml - definiowanie domyślnej palety kolorów

Aby zmienić domyślne pliki konfiguracyjne należy przekazać do konstruktora fasady odpowiednio skonfigurowany obiekt ładujący konfigurację.

```php
    $loader = new PHPPdf\Core\Configuration\LoaderImpl('/sciezka/do/pliku/nodes.xml', '/sciezka/do/pliku/complex-attributes.xml', '/sciezka/do/pliku/fonts.xml', '/sciezka/do/pliku/colors.xml');
    $facade = new PHPPdf\Core\Facade($loader);
```
    
Jeśli chcesz zmienić tylko jeden plik konfiguracyjny, powinieneś użyć jednej z metod LoaderImpl::set*:

```php
    $loader = new PHPPdf\Core\Configuration\LoaderImpl();
    $loader->setFontFile('/sciezka/do/pliku/fonts.xml');//dostępne metody: setFontFile, setNodeFile, setComplexAttributeFile, setColorFile
    $facade = new PHPPdf\Core\Facade($loader);
```

Można wykorzystać budowniczego fasady, który dodatkowo ma opcję do ustawiania cache, silnika renderującego oraz parsera dokumentów.
    
```php
    $builder = PHPPdf\Core\FacadeBuilder::create(/* można przekazać obiekt loadera konfiguracji */)
                                        ->setCache('File', array('cache_dir' => './cache'))
                                        ->setUseCacheForStylesheetConstraint(true); //szablony stylów również będą korzystały z cache

    $facade = $builder->build();
```

<a name="markdown"></a>
Markdown - wsparcie
----------------

Biblioteka wspiera podstawową (oficjalną) składnię markdown. Aby skonwertować dokument markdown do pdf'a, powinieneś skonfigurować obiekt Facade obiektem MarkdownDocumentParser. Możesz użyć FacadeBuilder, który zrobi to za Ciebie.

Przykład:

```php
    $facade = PHPPdf\Core\FacadeBuilder::create()
                                         ->setDocumentParserType(PHPPdf\Core\FacadeBuilder::PARSER_MARKDOWN)
                                         ->setMarkdownStylesheetFilepath(/** opcjonalna ścieżka do pliku z arkuszem stylów o składni xml */)
                                         ->build();
```

Domyślnie w dokumencie jest użyta czcionka helvetica. Jeśli chcesz użyć znaków utf-8 lub dostosować wynikowy dokument pdf, powinieneś dostarczyć swój własny arkusz stylów poprzez metodę FacadeBuilder::setMarkdownStylesheetFilepath. Struktura arkuszy stylów została opisana w jednym z poprzednich [rozdziałów](#stylesheet). Domyślnie arkusz stylów jest pusty, jeśli chcesz ustawić inną czcionkę, arkusz stylów powinien wyglądać:

```xml
    <stylesheet>
        <any font-type="DejaVuSans" />
    </stylesheet>
```

Wewnętrznie MarkdownDocumentParser konwertuje dokument markdown do html'a (poprzez bibliotekę [PHP markdown](https://github.com/wolfie/php-markdown)), następnie konwertuje html'a do xml'a i wreszcie xml'a do dokumentu pdf.

Miej na uwadze to, że jeśli w dokumencie markdown użyjesz surowego html'a, który nie będzie kompatibilny ze składnią xml wspieraną przez PHPPdf (np. nieistniejący atrybut lub nazwa tagu), dokument się nie sparsuje - zostanie wyrzucony wyjątek. Nie wszystkie tagi użyte w implementacji markdown są poprawnie wspierane przez PHPPdf, np. tagi "pre" oraz "code". Obecnie tag "pre" jest aliasem "div", a tag "code" jest aliasem do "span".

<a name="image-generation"></a>
Image generation engine
-----------------------

PHPPdf może również generować obrazki (jpg lub png) zamiast plików pdf. Aby to osiągnąć, musisz skonfigurować obiekt FacadeBuilder, przykład:

```php
    $facade = PHPPdf\Core\FacadeBuilder::create()
                                       ->setEngineType('image')
                                       ->build();

    //metoda render zwraca tablicę źródeł obrazków, jeden obrazek to jedna strona dokumentu pdf
    $images = $facade->render(...);
```

Domyślną biblioteką generującą obrazki jest Gd, możesz używać biblioteki Imagick, która oferuje lepszą jakość, więc jest rekomendowana jeśli masz możliwość zainstalowania Imagick na swoim serwerze. Aby ustawić bibliotekę która ma być wykorzystana, należy skonfigurować obiekt FacadeBuilder za pomocą metody setEngineOptions:

```php
    $builder = ...;
    $builder->setEngineOptions(array(
        'engine' => 'imagick',
        'format' => 'png',//png, jpeg lub wbmp
        'quality' => 60,//liczba całkowita od 0 do 100
    ));
```

Wspierane biblioteki graficzne: gd (domyślnie), imagick, gmagick. PHPPdf wykorzystuje bibliotekę [Imagine][2] jako interfejs do generowania plików graficznych.

<a name="limitations"></a>
Znane ograniczenia
-------------------

Poniżej przedstawiam listę ograniczeń obecnej wersji biblioteki:

* brak możliwości wstawiania zdjęcia do tekstu z opływem (float) - zostanie wprowadzone w kolejnych wersjach
* częściowa obsługa dla atrybutu float w tabelce (float może nie działać prawidłowo w tabelce)
* atrybut vertical-align działa nieprawidłowo, jeśli w elemencie z ustawionym tym atrybutem, jest więcej niż jeden element
* obramowanie nie zmienia rozmiaru elementu tak jak to jest w HTML - zabieg celowy, raczej nie planuję jego zmiany
* pliki png (zwłaszcza bez kompresji) są nieefektywne, powinny być używane pliki png z wysoką kompresją (poziom kompresji 6 lub większy) lub pliki jpeg
* nie wszystkie tagi są poprawnie wspierane, np. tag "pre" obecnie jest aliasem do tagu "div", a tag "code" jest aliasem do tagu "span"
* zagnieżdżanie linearnych tagów (text, span, code, page-info, page-number, a, b, i, em) jest jest prawidłowo wspierane. Jeśli jeden linearny tag zawiera inny, tylko tekst wewnątrz tych tagów jest połączony, style są wzięte z najbardziej zewnętrznego linearnego tagu.

<a name="todo"></a>
TODO - czyli plany
-------------------

* automatycznie generowany spis treści
* poprawa działania tabelek, definiowanie nagłówków i stopek dla tabeli
* obsługa prosych wykresów słupkowych i kołowych

<a name="requirements"></a>
Wymagania techniczne
---------------------

Biblioteka działa pod php 5.3+.

[1]: https://github.com/psliwa/PdfBundle
[2]: https://github.com/avalanche123/Imagine
[3]: http://framework.zend.com/manual/en/zend.barcode.objects.html
