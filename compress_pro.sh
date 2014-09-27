
if [ "a$1" = "a" ]; then 
  echo Usage compress.sh 1.1.0
  exit
fi

#newversion="1.1.7"
newversion=$1

echo VERSION=$newversion >> configpro.inc

if [ "a$2" != "a" ]; then 
  # $2 is the comment of this version
  # echo -e = enable backslash interpretation
  echo -e "`date +%Y-%m-%d` $newversion\t$2" >> news.inc
fi 

extension="toomanyfiles pro"

echo Compressione di $extension $newversion. Rimuovo precedente versione


cd /cygdrive/e/DeV/Joomla/TooManyFiles/com_toomanyfiles_pro

# File di installazione pacchetto:
grep -rl -P "<version>.*</version>" . | grep "xml" | xargs sed -i -e "s@<version>.*</version>@<version>$newversion</version>@"
echo "<version>$newversion</version> Aggiornata."


echo Create zips for: 
echo - pro component
zipped="${extension}_$newversion.zip"
zip -9 -r $zipped $extension 
echo ... ok

echo Compression done. Now upload

#scp $zipped root@webserver.tmg.it:/home/fasterjoomla/public_html/files/extensions/$extension/

#echo update on github

#git add "*"
#git commit -am "Version $newversion"
#git push


echo Done component pro!

