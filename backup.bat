@echo off 
cls

@REM echo %date%
@REM echo 012345678901234567890
@REM echo 11111111112
@REM echo.
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"

set "today= %DD%%MM%%YYYY%_%HH%%Min%%Sec%"
@REM echo %today%
set LogFileDir=C:\Users\Sripal Jain\Desktop\
set LogFile=%LogFileDir%finance_demo_%today%_PC.sql
@REM echo "%LogFile%"
D:\xampp\mysql\bin\mysqldump.exe --user=root --password= --result-file="%LogFile%" finance 
echo Done!
pause
exit
