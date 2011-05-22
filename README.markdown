Documentation draft (PL)
========================

1. Parsowanie dokumentu i tworzenie pdf'a.
==========================================

Oto najprostrzy sposób wykorzystania biblioteki:

    //zarejestrowanie autoloadera PHPPdf oraz vendor (Zend_Pdf)
    require_once 'PHPPdf/Autoloader.php';
    PHPPdf\Autoloader::register();
    PHPPdf\Autoloader::register('sciezka/do/biblioteki/lib/vendor');

    $facade = new PHPPdf\Parser\Facade();

    //$documentXml i $stylesheetXml to ciągi znaków zawierające dokumenty XML, $stylesheetXml jest opcjonalne
    $content = $facade->render($documentXml, $stylesheetXml);

    header('Content-Type: application/pdf');
    echo $content;

2. Podstawowa struktura dokumentu.
==================================

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

Korzeń dokumentu musi się nazywać "pdf". Element "dynamic-page" jest stroną, która się dynamicznie dzieli gdy zostanie przepełniona. Alternatywą jest tag "page", czyli pojedyńcza niepodzielna strona. Są istotne różnice w nadawaniu atrybutom wartości w stosunku do HTML. Aby nadać obramowanie i tło warstwie, należy użyć specjalnego elementu "stylesheet". Przykład:

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

Atrybuty elementom można nadawać za pomocą atrybutów XML bezpośrednio po nazwie tagu lub też za pomocą wspomnianego tagu "stylesheet". Upiększenia, czyli rodzaj atrybutów złożonych, można dodawać tylko poprzez tag "enhancement" zagniezdżony w "stylesheet". Nie istnieje atrybut "style" znany z HTML'a.

Biblioteka jest bardzo rygorystyczna pod względem poprawności tagów i atrybutów. Jeśli zostanie użyty nieistniejący tag lub atrybut, dokument się nie sparsuje - zostanie wyrzucony wyjątek z odpowiednią treścią.

3. Dziedziczenie.
=================

Atrybut "id" ma całkowicie inne znaczenie niż w HTML'u. Id musi być unikalne w obrębie dokumentu, w przeciwnym wypadku wystąpi błąd parsowania. Służy on do identyfikowania elementów przy dziedziczeniu. Przykład:

    <pdf>
        <dynamic-page>
            <div id="warstwa-1" color="red" font-type="verdana" font-size="16">
                <stylesheet>
                    <enhancement name="border" color="green" />
                </stylesheet>
                Warstwa 1
            </div>
            <div extends="warstwa-1">
                Warstwa 2 dziedzicząca style (typ, atrybuty i upiększenia) po warstwie 1)
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

4. Struktura arkusza stylów.
============================

Arkusze stylów muszą się znajdować w osobnym pliku. Składnia arkusza stylów:

    <stylesheet>
        <div class="klasa">
            <!-- atrybuty i upiększenia zagnieżdzone w ścieżce selektora div.klasa -->
            <attribute name="font-size" value="12" />
            <attribute name="color" value="grey" />
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

5. Standardowe tagi.
====================

Biblioteka obsługuje podstawowe tagi zaczerpnięte z języka HTML: div, p, table, tr, td, b, strong, span, h1, h2, h3, h4, h5, img, br
Ponadto obsługiwane są niestandardowe tagi:

* dynamic-page - strona, która się dynamicznie dzieli gdy zostaje przepełniona
* page - pojedyncza strona
* page-break - złamanie strony, jest to element podrzędny dynamic-page, czyli musi być bezpośrednim dzieckiem tego elemntu!
* column-layout - podział obszaru roboczego na kolumny, dodatkowe atrybuty: number-of-columns oraz margin-between-columns
* column-break - złamanie kolumny, jest to element podrzędny columns (TODO)

Istnieją tagi, które służą jedynie do określania wartości atrybutów, zbioru atrybutów lub zbioru elementów:

* stylesheet - style dla elementu nadrzędnego
* attribute - atrybut, bezpośredni element podrzędny dla "stylesheet". Wymagane atrybute tego elementu: name - nazwa atrybutu, value - wartość atrybutu
* enhancement - atrybut złożony (upiększenie), bezpośredni element podrzędny dla "stylesheet". Wymagany atrybut tego elementu: name - nazwa.
* placeholders - definiuje wartości "slotów" dla elementu podrzędnego. Elementy podrzędne "placeholders" są specyficzne dla tagu nadrzędnego.
* metadata - definiuje dane meta dla dokumentu pdf, bezpośredni element podrzędny korzenia dokumentu (TODO)

6. Atrybuty.
============

* width oraz height: ustawienie wysokości i szerokości na sztywno
* margin (margin-top, margin-bottom, margin-left, margin-right): margines podobny jak w HTML/CSS z taką różnicą, że marginesy sąsiadów się sumują. Dla marginesów bocznym możliwa jest wartość "auto" - działa podobnie jak w HTML/CSS
* padding (padding-top, padding-bottom, padding-left, padding-right): dopełnienie wewnętrzne - tak jak w HTML/CSS
* font-type - typ czcionki. Nazwa czcionki musi występować w pliku konfiguracyjnym fonts.xml, w przeciwnym wypadku zostanie wyrzucony wyjątek
* font-size - rozmiar czcionki w punktach
* font-style - styl czcionki, dozwolone wartości: normal, bold, italic, bold-italic
* color - kolor tekstu. Przyjmowane wartości takie jak w HTML/CSS
* display - sposób wyświetlenia (block|inline|none)
* splittable - określa czy element może zostać podzielony na dwie strony, dla większości elementów domyślna wartość to true
* float - działanie podobne, aczkolwiek nie takie same jak w HTML/CSS. Wartości left|none|right, domyślnie none
* line-height - działanie takie jak w HTML/CSS. Domyślna wartość to 1.2*font-size
* text-align - działanie takie jak w HTML/CSS. Wartości left|center|right, domyślnie left. Obecnie nie działa poprawnie dla tekstu wymieszanego z tagami formatującymi (np. "span").
* page-break - łamie stronę jeśli element jest bezpośrednim dzieckiem elementu dynamic-page
* colspan, rowspan - działanie analogiczne do atrybutów html (rowspan jeszcze nie jest zaimplementowane)

7. Upiększenia (atrybuty złożone)
=================================

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

Można dodawać kilka upiększeń tego samego typu - np. 3 różne obramowania:

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

8. Powtarzalne nagłówki i stopki.
=================================

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

9. Podział strony na kolumny.
=============================

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

10. Konfiguracja.
================

Biblioteka ma 3 podstawowe pliki konfiguracyjne, które pozwalają na dostosowanie biblioteki do swoich potrzeb oraz do jej rozszerzenia.

* enhancements.xml - przypisywanie klas upiększeń (atrybutów złożonych) pod nazwy logiczne, które identyfikują dany typ upiększenia w obrębie całej biblioteki
* glyphs.xml - definiowanie tagów dostępnych w dokumencie xml wraz z domyślnymi stylami oraz obiektami formatującymi
* fonts.xml - definowanie czcionek i przypisywanie ich do nazw logicznych, które identyfikują daną czcionkę w obrębie całej biblioteki

Aby zmienić domyślne pliki konfiguracyjne należy użyć obiektu klasy FacadeBuilder aby nowe ścieżki przekazać do obiektu fasady:

    $builder = PHPPdf\Parser\FacadeBuilder::create()->setGlyphsConfigFile('...')->setFontsConfigFile('...');
    $facade = $builder->build();

Można ustawić cache dla plików konfiguracyjnych oraz szablonów stylów:

    $builder = ...;

    $facade = $builder->setCache('File', array('cache_dir' => './cache')) //cache będzie przechowywane w pliku w podanym folderze
                      ->setUseCacheForStylesheetConstraint(true) //szablony stylów również będą korzystały z cache
                      ->build();

11. Znane ograniczenia.
======================

Poniżej przedstawiam listę ograniczeń obecnej wersji biblioteki:

* brak możliwości wstawiania zdjęcia do tekstu z opływem (float) - zostanie wprowadzone w kolejnych wersjach
* niepoprawne działanie wyrównania do środka i do prawej testu z zagnieżdżonymi tagami (np. "Jakiś takst <span>inny tekst</span>") - zostanie poprawione w kolejnych wersjach
* brak justrowania - zostanie wprowadzone w kolejnych wersjach
* obramowanie nie zmienia rozmiaru elementu tak jak to jest w HTML - zabieg celowy, raczej nie planuję jego zmiany

12. TODO - czyli plany.
=======================

* obsługa adnotacji
* obsługa metadanych dokumentu
* obsługa zakładek
* poprawa interpretacji wartości atrybutów i rozkładu elementów w dokumencie
* obsługa podziału strony na kolumny
* poprawa działania tabelek, definiowanie nagłówków i stopek dla tabeli

13. Wymagania techniczne.
=========================

Biblioteka działa pod php 5.3+.