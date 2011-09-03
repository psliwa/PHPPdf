Dokumentacja
============

Spis treści
-----------------

1. [Instalacja](#installation)
1. [Symfony2 bundle](#symfony2-bundle)
1. [Parsowanie dokumentu i tworzenie pdf'a](#parsing)
1. [Podstawowa struktura dokumentu](#structure)
1. [Dziedziczenie](#inheritance)
1. [Struktura stylów](#stylesheet)
1. [Standardowe tagi](#tags)
1. [Atrybut](#attributes)
1. [Atrybuty złożone](#complex-attributes)
1. [Hiperlinki](#hyperlinks)
1. [Zakładki](#bookmarks)
1. [Notatki](#notes)
1. [Powtarzalne nagłówki i stopki](#headers)
1. [Znaki wodne](#watermarks)
1. [Wykorzystanie istniejącego dokumentu jako szablon](#templates)
1. [Podział strony na kolumny](#columns)
1. [Łamanie stron i kolumn](#page-break)
1. [Metadane](#metadata)
1. [Konfiguracja](#configuration)
1. [Znane ograniczenia](#limitations)
1. [TODO - czyli plany](#todo)
1. [Wymagania techniczne](#requirements)

Instalacja
----------

Biblioteka opcjonalnie korzysta z innych bibliotek (komponent Symfony2 do DependencyInjection), które można pobrać wywołując polecenie:

    php vendors.php
    
<a name="symfony2-bundle"></a>
Symfony2 bundle
----------------

Tutaj znajduje się [Symfony2 bundle][1] integrujący tą bibliotekę z Symfony2.

<a name="parsing"></a>
Parsowanie dokumentu i tworzenie pdf'a.
---------------------------------------

Najprostrzy sposób wykorzystania biblioteki:

    //zarejestrowanie autoloadera PHPPdf oraz vendor (Zend_Pdf)
    require_once 'PHPPdf/Autoloader.php';
    PHPPdf\Autoloader::register();
    PHPPdf\Autoloader::register('sciezka/do/biblioteki/lib/vendor');

    $facade = new PHPPdf\Parser\Facade(new PHPPdf\Configuration\Loader());

    //$documentXml i $stylesheetXml to ciągi znaków zawierające dokumenty XML, $stylesheetXml jest opcjonalne
    $content = $facade->render($documentXml, $stylesheetXml);

    header('Content-Type: application/pdf');
    echo $content;

<a name="structure"></a>
Podstawowa struktura dokumentu.
-------------------------------

Biblioteka bazuje na formacie XML przypominającym HTML, ale w żadnym wypadku nie jest to HTML - niektóre tagi się różnią, interpretacja niektórych atrybutów jest inna niż w standardzie HTML i CSS, sposób dodawania atrybutów również jest inny. Najprostrzy dokument ma następującą strukturę:

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
    
Zalecane jest dodawanie następującej deklaracji DOCTYPE do dokumentów, pozwala ona na zaminę encji na wartości:

    <!DOCTYPE pdf SYSTEM "%resources%/dtd/doctype.dtd">

Korzeń dokumentu musi się nazywać "pdf". Element "dynamic-page" jest stroną, która się dynamicznie dzieli gdy zostanie przepełniona. Alternatywą jest tag "page", czyli pojedyńcza niepodzielna strona. Są różnice w nadawaniu atrybutom wartości w stosunku do HTML. Aby nadać obramowanie i tło warstwie należy posłużyć się atrybutem złożonym "border" oraz "background", atrybuty te mają swoje własne właściwości, np. kolor, rozmiar, zaokrąglenie. Alternatywną składnią do ustawiania atrybutów oraz atrybutów złożonych (enhancement) jest element "stylesheet". Przykład:

    <pdf>
        <dynamic-page>
            <div color="red" border.color="black" background.color="pink">
                Ten tekst jest czerwony na różowym tle w czarnym obramowaniu
            </div>
        </dynamic-page>
    </pdf>
    
Alternatywna składnia (element stylesheet):

    <pdf>
        <dynamic-page>
            <div>
                <stylesheet>
                    <attribute color="red" />
                    <enhancement name="border" color="black" />
                    <enhancement name="background" color="pink" />
                </stylesheet>
                Ten tekst jest czerwony na różowym tle w czarnym obramowaniu
            </div>
        </dynamic-page>
    </pdf>

Atrybuty można nadawać za pomocą atrybutów XML bezpośrednio po nazwie tagu lub też za pomocą wspomnianego tagu "stylesheet". Nie istnieje atrybut "style" znany z HTML'a.

Biblioteka jest bardzo rygorystyczna pod względem poprawności tagów i atrybutów. Jeśli zostanie użyty nieistniejący tag lub atrybut, dokument się nie sparsuje - zostanie wyrzucony wyjątek z odpowiednią treścią.

<a name="inheritance"></a>
Dziedziczenie
--------------

Atrybut "id" ma całkowicie inne znaczenie niż w HTML'u. Id musi być unikalne w obrębie dokumentu, w przeciwnym wypadku wystąpi błąd parsowania. Służy on do identyfikowania elementów przy dziedziczeniu. Przykład:

    <pdf>
        <dynamic-page>
            <div id="warstwa-1" color="red" font-type="judson" font-size="16">
                <stylesheet>
                    <enhancement name="border" color="green" />
                </stylesheet>
                Warstwa 1
            </div>
            <div extends="warstwa-1">
                Warstwa 2 dziedzicząca style (typ, atrybuty proste i złożone) po warstwie 1)
            </div>
        </dynamic-page>
    </pdf>

Druga warstwa dziedziczy wszstkie atrybuty proste i złożone (upiększenia) po pierwszej, nawet te które zostały nadane z zewnętrznego arkuszu stylów.

Priorytety nadawania wartości atrybutom:

1. Tag stylesheet wewnątrz elementu
2. Zdefiniowanie atrybutów bezpośrednio po nazwie tagu
3. Atrybuty pobrane z arkusza stylów
4. Atrybuty odziedziczone po innym elemencie

Przykład:

    <pdf>
        <page>
            <div id="1" color="#cccccc" height="100" text-align="right">
            </div>
            <div extends="1" color="#aaaaaa" height="150">
                <stylesheet>
                    <attribute name="height" value="200" />
                </stylesheet>
            </div>
        </page>
    </pdf>

Drugi "div" będzie miał następujące atrybuty:
- text-align: right
- color: #aaaaaa
- height: 200

<a name="stylesheet"></a>
Struktura arkusza stylów.
-------------------------

Arkusze stylów muszą się znajdować w osobnym pliku, krótki (jako atrybut xml) oraz długi (jako osobne tagi) sposób definicji stylów jest wspierany. Składnia arkusza stylów:

Krótki sposób:

    <stylesheet>
        <!-- style są wbudowane w tag jako atrybuty xml, atrybut "class" ma takie samo znaczenie co w HTML/CSS -->
        <div class="class" font-size="12" color="gray" background.color="yellow">
            <!-- element zagnieżdżony, odpowiednik selektora z CSS: "div.class p" -->
            <p margin="10 15">
            </p>
        </div>

        <!-- odpowiednik selektora z CSS: ".another-class", tag "any" jest wildcardem (każdy tag do niego pasuje) -->
        <any class="another-class" text-align="right">
        </any>

        <h2 class="header">
            <span font-size="9">
            </span>
            
            <div font-style="bold">
            </div>
        </h2>
    </stylesheet>

Długi sposób:

    <stylesheet>
        <div class="klasa">
            <!-- atrybuty proste i złożone zagnieżdzone w ścieżce selektora div.klasa -->
            <attribute name="font-size" value="12" />
            <attribute name="color" value="grey" />
            <!-- odpowiednik atrybutu background.color -->
            <enhancement name="background" color="yellow" />

            <!-- kolejny element, odpowiadająca składnia selektora z CSS: "div.klasa p" -->
            <p>
                <attribute name="margin" value="10 15" />
            </p>
        </div>

        <!-- odpowiednik selektora ".inna-klasa" z CSS, tag "any" oznacza dowolny tag -->
        <any class="inna-klasa">
            <attribute name="text-align" value="right" />
        </any>

        <h2 class="naglowek">
            <span>
                <attribute name="font-size" value="9" />
            </span>
            <div>
                <attribute name="font-style" value="bold" />
            </div>
        </h2>
    </stylesheet>

<a name="tags"></a>
Standardowe tagi
-----------------

Biblioteka obsługuje podstawowe tagi zaczerpnięte z języka HTML: div, p, table, tr, td, b, strong, span, a, h1, h2, h3, h4, h5, img, br, ul, li
Ponadto obsługiwane są niestandardowe tagi:

* dynamic-page - strona, która się dynamicznie dzieli gdy zostaje przepełniona
* page - pojedyncza strona
* page-break, column-break, break - złamanie strony lub kolumny, jest to element podrzędny dynamic-page lub column-layout, czyli musi być bezpośrednim dzieckiem tego elemntu! Te trzy tagi są aliasami.
* column-layout - podział obszaru roboczego na kolumny, dodatkowe atrybuty: number-of-columns oraz margin-between-columns

Istnieją tagi, które służą jedynie do określania wartości atrybutów, zbioru atrybutów lub zbioru elementów:

* stylesheet - style dla elementu nadrzędnego
* attribute - atrybut, bezpośredni element podrzędny dla "stylesheet". Wymagane atrybute tego elementu: name - nazwa atrybutu, value - wartość atrybutu
* enhancement - atrybut złożony (upiększenie), bezpośredni element podrzędny dla "stylesheet". Wymagany atrybut tego elementu: name - nazwa.
* placeholders - definiuje wartości "slotów" dla elementu podrzędnego. Elementy podrzędne "placeholders" są specyficzne dla tagu nadrzędnego.
* metadata - definiuje dane meta dla dokumentu pdf, bezpośredni element podrzędny korzenia dokumentu (TODO)
* behaviours - definiuje zachowania dla elementu nadrzędnego. Obsługiwane zachowania: ref, href, bookmark, note (działanie takie samo jak dla atrybutów o tych samych nazwach)

<a name="attributes"></a>
Atrybuty
---------

* width oraz height: ustawienie wysokości i szerokości na sztywno, nie są obsługiwane jednostki. Jest możliwe użycie wartości relatywnych wyrażonych w procentach
* margin (margin-top, margin-bottom, margin-left, margin-right): margines podobny jak w HTML/CSS z taką różnicą, że marginesy sąsiadów się sumują. Dla marginesów bocznym możliwa jest wartość "auto" - działa podobnie jak w HTML/CSS
* padding (padding-top, padding-bottom, padding-left, padding-right): dopełnienie wewnętrzne - tak jak w HTML/CSS
* font-type - typ czcionki. Nazwa czcionki musi występować w pliku konfiguracyjnym fonts.xml, w przeciwnym wypadku zostanie wyrzucony wyjątek
* font-size - rozmiar czcionki w punktach, brak obsługi jednostek wielkości
* font-style - styl czcionki, dozwolone wartości: normal, bold, italic, bold-italic
* color - kolor tekstu. Przyjmowane wartości takie jak w HTML/CSS
* display - sposób wyświetlenia (block|inline|none)
* splittable - określa czy element może zostać podzielony na dwie strony, dla większości elementów domyślna wartość to true
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

<a name="complex-attributes"></a>
Atrybuty złożone
----------------

* border:
    - color: kolor obramowania
    - style: styl obramowania, możliwe wartości: solid (linia ciągła), dotted (predefiniowana linia przerywana) lub własna definicja w postaci ciągu liczb oddzielonych spacjami
    - type: które krawędzie mają mieć obramowanie - domyślnie "top+bottom+left+right", czyli każda krawędź. Możliwa wartość none (wyłączenie obramowania)
    - size: rozmiar obramowania
    - radius: zaokrąglenie rogów w radianach (uwaga: przy ustawionym tym parametrze ignorowany jest parametr typu - zawsze obramowanie z zaokrąglonymi rogami jest pełne)
    - position: przesunięcie obramowania względem orginalnego położenia. Wartości dodatnie rozszerzają obramowanie, wartości ujemnie zwężają. Dzięki manipulacji tym parametrem i dodaniu kilku obramowań, można uzyskać złożone i skomplikowane obramowania.

* background:
    - color: kolor tła
    - image: obrazek tła
    - repeat: sposób powtarzania obrazka (none|x|y|all)
    - radius: zaokrąglanie rogów tła w radianach (w chwili obecnej działa tylko dla koloru, nie obrazka)
    - use-real-dimension: atrybut wykorzystany jak narazie dla tagu page (lub dynamic-page). True jeśli zapełniać marginesy, false w przeciwnym wypadku.
    - image-width: szerokość obrazka tła, może być wartością procentową
    - image-height: wysokość obrazka tła, może być wartością procentową

Można dodawać kilka upiększeń tego samego typu (np. 3 różne obramowania) używając tagu "stylesheet" zamiast krótkiej notacji ("border.color"):

    <pdf>
        <dynamic-page>
            <div>
                <stylesheet>
                    <!-- Górna i dolna krawędź czerwona, boczne żółto-szare -->
                    <enhancement name="border" color="red" type="top+bottom" />
                    <enhancement id="borderLeftAndRight" name="border" color="yellow" type="left+right" size="4" />
                    <enhancement id="outerBorderLeftAndRight" name="border" color="gray" type="left+right" size="2" position="1" />
                </stylesheet>
            </div>
        </dynamic-page>
    </pdf>

W tym przykładzie drugie obramowanie ma identyfikator "borderLeftAndRight", jakby go nie było to atrybuty drugiego obramowania zostały by złączone z atrybutami z pierwszego obramowania. Domyślny identyfikator "id" jest równy atrybutowi "name". Identyfikatory "id" dla upiększeń (enhancements) nie mają nic wspólnego z atrybutami "id" dla elementów (glyphów). Można tworzyć obramowania złożone manipulując pozycją, tak jak w powyższym przykładzie (outerBorderLeftAndRight).

<a name="hyperlinks"></a>
Hiperlinki
----------

Biblioteka wspiera wewnętrzne oraz zewnętrzne hiperłącza. Zewnętrzne hiperłącza linkują do adresów url, wewnętrzne zaś do innego tagu wewnątrz dokumentu.

Przykład:

    <pdf>
        <dynamic-page>
            <a href="http://google.com">idź do google.com</a>
            <br />
            <a ref="some-id">idź do innego tagu</a>
            <page-break />
            <p id="some-id">Tak, to jest inny tag! ;)</p>
        </dynamic-page>
    </pdf>

Każdy element ma attrybuty "href" oraz "ref", nawet div. Nie możesz zagnieżdżać elementów wewnątrz tagu "a". Jeśli chcesz użyć np. img jako linka, powinieneś wykorzystać do tego atrybut "href" (zewnętrzny link) lub "ref" (wewnętrzny link) bezpośrednio w tagu "img".

<a name="bookmarks"></a>
Zakładki
--------

Każdy tag ma atrybut "bookmark", jeśli przypiszesz mu jakąś wartość to zostanie utworzona zakładka, która linkuje do tego tagu. Zakładka tagu rodzica jest również rodzicem zakładek dzieci tego tagu.

Przykład:

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
		    <div bookmark="inny bookmark rodzica>
		       Jakaś treść
		    </div>
		</dynamic-page>
    </pdf>

Powyższa struktura utworzy poniższą strukturę zakładek:

* bookmark rodzica
    - bookmark dziecka
    - inny bookmark dziecka
* inny bookmark rodzica

<a name="notes"></a>
Notatki
-------

Notatka może zostać dodana poprzez atrybut "note".

Przykład:

    <pdf>
        <dynamic-page>
            <div note="treść notatki"></div>
        </dynamic-page>
    </pdf>

Parser xml normalizuje wartości atrybutów, czego skutkiem jest ignorowanie znaków nowej linii. Jeśli chcesz dodać notatkę, w której znaki nowej lini są ważne, możesz skorzystać ze składni:

    <pdf>
        <dynamic-page>
            <div>
                <behaviours>
                    <note>Tekst notatki</note>
                </behaviours>
            </div>
        </dynamic-page>
    </pdf>

<a name="headers"></a>
Powtarzalne nagłówki i stopki
------------------------------

Aby dodać powtarzalny nagłówek i/bądź stopkę należy wykorzystać tag "placeholders". Niektóre elementy mają specjalne "sloty": strona ma nagłówek i stopkę, tabela może mieć nagłówek (TODO: jeszcze nie zaimplementowane) itp.

    <pdf>
        <dynamic-page>
            <placeholders>
                <header>
                    <div height="50" width="100%">
                        Nagłówek
                    </div>
                </header>
                <footer>
                    <div height="50" width="100%">
                        Stopka
                    </div>
                </footer>
            </placeholders>
        </dynamic-page>
    </pdf>

Nagłówek i stopka muszą mieć bezpośrednio określoną wysokość. Wysokość ta sumuje się z marginesami górnym i dolnym, czyli rozmiar roboczy strony jest to rozmiar pomniejszony o marginesy górny i dolny oraz o wysokość stopki i nagłówka.

W nagłówku i stopce można korzystać z specjalnego tagu "<page-info></page-info>" który wyświetla informacje o obecnym numerze strony oraz łącznej liczbie stron w obrębie obecnego elementu "dynamic-page" (nie łączną liczbę stron w całym dokumencie!). Element ten ma swoje atrybuty, z których najważniejszym jest format.

    <!-- ciach -->
        <header>
            <page-info format="strona %s na %s"></page-info>
        </header>
    <!-- ciach -->

<a name="watermarks"></a>
Znaki wodne
-----------

Strona ma placeholder o nazwie "watermark". Jako znak wodny można użyć element blokowy, który może zawierać dzieci, np. div, p, h1 (ale nie span, tekst czy img). Jeśli chcesz użyć tagu img lub tekstu jako znak wody, powinieneś ten element opakować np. w tag div.

Przykład:

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

<a name="templates"></a>
Wykorzystanie istniejącego dokumentu jako szablon
-------------------------------------------------

Tag "page" oraz "dynamic-page" posiadają atrybut "document-template", który pozwala na wskazanie pliku z dokumentem, który ma być wykorzystany jako szablon. Dla tagu "page" szablonem strony będzie pierwsza strona wskazanego dokumentu, zaś dla tagu "dynamic-page" szablonami kolejnych stron, będą odpowiednie, kolejne strony wskazanego dokumentu.

Przykład:

    <pdf>
        <dynamic-page document-template="sciezka/do/pliku.pdf">
            <div>Jakaś treść</div>
        </dynamic-page>
    </pdf>

<a name="columns"></a>
Podział strony na kolumny.
--------------------------

Strona może być podzielona na kolumny.

    <pdf>
        <dynamic-page>
            <column-layout>
                <div width="100%" height="2500">
                    <stylesheet>
                        <enhancement name="background" color="green" />
                    </stylesheet>
                </div>
            </column-layout>
        </dynamic-page>
    </pdf>

Powyższy xml określa kilka stron dokumentu pdf z zielonymi prostokątami podzielonymi na 2 kolumny. Tag "column-layout" ma dwa dodatkowe atrybuty: number-of-columns oraz margin-between-columns. Domyślna wartość tych atrybutów to odpowiednio 2 oraz 10.

<a name="page-break"></a>
Łamanie stron i kolumn
----------------------

Strona i kolumna może być ręcznie złamana przez jeden z tagów: page-break, column-break, break. Każdy z tych tagów ma dokładnie takie samo znaczenie. Te tagi muszą być bezpośrednimi dziećmi łamanego elementu (tagów dynamic-page lub column-layout).

Jeśli chcesz uniknąć automatycznego łamania strony lub kolumny dla szczególnego tagu, powinieneś wyłączyć atrybut "splittable" dla tego tagu.

Przykład:

    <pdf>
        <dynamic-page>
            <div splittable="false">ten div nie będzie automatycznie łamany</div>
        </dynamic-page>
    </pdf>

<a name="metadata"></a>
Metadane
--------

Metadane mogą zostać dodane za pomocą atrybutów korzenia dokumentu. Metadane które można ustawić to: Creator, Keywords, Subject, Author, Title, ModDate, CreationDate oraz Trapped. Nazwy tych atrybutów są wrażliwe na wielkość znaków.

Przykład:

    <pdf Author="Piotr Śliwa" Title="Dokument testowy">
        <!-- jakieś inne elementy -->
    </pdf>

<a name="configuration"></a>
Konfiguracja
-------------

Biblioteka ma 3 podstawowe pliki konfiguracyjne, które pozwalają na dostosowanie biblioteki do swoich potrzeb oraz do jej rozszerzenia.

* enhancements.xml - przypisywanie klas upiększeń (atrybutów złożonych) pod nazwy logiczne, które identyfikują dany typ upiększenia w obrębie całej biblioteki
* glyphs.xml - definiowanie tagów dostępnych w dokumencie xml wraz z domyślnymi stylami oraz obiektami formatującymi
* fonts.xml - definowanie czcionek i przypisywanie ich do nazw logicznych, które identyfikują daną czcionkę w obrębie całej biblioteki

Aby zmienić domyślne pliki konfiguracyjne należy przekazać do konstruktora fasady odpowiednio skonfigurowany obiekt ładujący konfigurację.

    $loader = new PHPPdf\Configuration\LoaderImpl('/sciezka/do/pliku/glyphs.xml', '/sciezka/do/pliku/enhancements.xml', '/sciezka/do/pliku/fonts.xml');
    $facade = new PHPPdf\Parser\Facade($loader);

Można wykorzystać budowniczego fasady, który jak narazie ma opcje do ustawiania cache.
    
    $builder = PHPPdf\Parser\FacadeBuilder::create(/* można przekazać obiekt loadera konfiguracji */)
                                          ->setCache('File', array('cache_dir' => './cache'))
                                          ->setUseCacheForStylesheetConstraint(true); //szablony stylów również będą korzystały z cache

    $facade = $builder->build();

Są dwie implementacje loaderów konfiguracji, zwykła oraz korzystająca z komponentu DependencyInjection z Symfony2. Druga implementacja daje większą elastyczność w konfigurowaniu biblioteki. Domyślnie używany jest loader, który nie korzysta z DI.

<a name="limitations"></a>
Znane ograniczenia
-------------------

Poniżej przedstawiam listę ograniczeń obecnej wersji biblioteki:

* brak możliwości wstawiania zdjęcia do tekstu z opływem (float) - zostanie wprowadzone w kolejnych wersjach
* obramowanie nie zmienia rozmiaru elementu tak jak to jest w HTML - zabieg celowy, raczej nie planuję jego zmiany

<a name="todo"></a>
TODO - czyli plany
-------------------

* automatycznie generowany spis treści
* obsługa metadanych dokumentu
* poprawa interpretacji wartości atrybutów i rozkładu elementów w dokumencie
* poprawa działania tabelek, definiowanie nagłówków i stopek dla tabeli
* obsługa rowspan, nagłówka i stopki dla tabeli
* poprawienie wyliczania minimalnej wielkości komórki tabeli gdy jest użyty colspan
* refaktoryzacja

<a name="requirements"></a>
Wymagania techniczne
---------------------

Biblioteka działa pod php 5.3+.

[1]: https://github.com/psliwa/PdfBundle
