; POSG Windows Installer (Inno Setup)
; Build with: ISCC.exe POSG_Setup.iss

#define AppName "POSG"
#define AppVersion "1.0.4"
#define PublisherName "Glory Tech"

[Setup]
AppId={{A3B41A9E-4C48-4D6C-A64A-5E9BB9D38B27}
AppName={#AppName}
AppVersion={#AppVersion}
AppPublisher={#PublisherName}
DefaultDirName={code:GetInstallDir}
DisableDirPage=yes
DisableProgramGroupPage=yes
OutputDir=build
OutputBaseFilename=POSG_Installer
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin

[Languages]
Name: "arabic"; MessagesFile: "compiler:Languages\Arabic.isl"

[Tasks]
Name: "desktopicon"; Description: "إنشاء اختصار على سطح المكتب"; GroupDescription: "اختصارات:"; Flags: unchecked

[Files]
Source: "..\..\*"; DestDir: "{app}"; Flags: recursesubdirs createallsubdirs ignoreversion; Excludes: ".git\*,installer\windows\build\*,storage\logs\*"
Source: "scripts\post_install.bat"; DestDir: "{tmp}"; Flags: deleteafterinstall ignoreversion
Source: "scripts\update_config.ps1"; DestDir: "{tmp}"; Flags: deleteafterinstall ignoreversion

[Run]
Filename: "{tmp}\post_install.bat"; \
    Parameters: """{code:GetXamppPath}"" ""{app}"" ""{code:GetDbPort}"" ""{code:GetDbUser}"" ""{code:GetDbPass}"""; \
    StatusMsg: "جاري تهيئة قاعدة البيانات وإعداد الاتصال..."; Flags: waituntilterminated runhidden
Filename: "{code:GetAppUrl}"; Description: "فتح النظام الآن"; Flags: postinstall shellexec nowait skipifsilent

[INI]
Filename: "{userdesktop}\POSG.url"; Section: "InternetShortcut"; Key: "URL"; String: "{code:GetAppUrl}"; Tasks: desktopicon

[Code]
var
  ConfigPage: TInputQueryWizardPage;

function TrimSlash(const S: string): string;
var
  R: string;
begin
  R := S;
  while (Length(R) > 0) and ((R[Length(R)] = '\') or (R[Length(R)] = '/')) do
    Delete(R, Length(R), 1);
  Result := R;
end;

function GetXamppPath(Param: string): string;
begin
  if ConfigPage = nil then
  begin
    Result := 'C:\xampp';
    exit;
  end;
  Result := TrimSlash(ConfigPage.Values[0]);
end;

function GetApachePort(Param: string): string;
begin
  if ConfigPage = nil then
  begin
    Result := '80';
    exit;
  end;

  Result := Trim(ConfigPage.Values[1]);
  if Result = '' then Result := '80';
end;

function GetDbPort(Param: string): string;
begin
  if ConfigPage = nil then
  begin
    Result := '3306';
    exit;
  end;

  Result := Trim(ConfigPage.Values[2]);
  if Result = '' then Result := '3306';
end;

function GetDbUser(Param: string): string;
begin
  if ConfigPage = nil then
  begin
    Result := 'root';
    exit;
  end;

  Result := Trim(ConfigPage.Values[3]);
  if Result = '' then Result := 'root';
end;

function GetDbPass(Param: string): string;
begin
  if ConfigPage = nil then
  begin
    Result := '';
    exit;
  end;

  Result := ConfigPage.Values[4];
end;

function GetAppUrl(Param: string): string;
var
  ApachePort: string;
begin
  ApachePort := GetApachePort('');
  if (ApachePort = '') or (ApachePort = '80') then
    Result := 'http://localhost/POSG/public'
  else
    Result := 'http://localhost:' + ApachePort + '/POSG/public';
end;

function GetInstallDir(Param: string): string;
begin
  Result := AddBackslash(GetXamppPath('')) + 'htdocs\POSG';
end;

function NextButtonClick(CurPageID: Integer): Boolean;
var
  XamppRoot: string;
begin
  Result := True;
  if CurPageID = ConfigPage.ID then
  begin
    XamppRoot := GetXamppPath('');
    if XamppRoot = '' then
    begin
      MsgBox('يرجى إدخال مسار XAMPP.', mbError, MB_OK);
      Result := False;
      exit;
    end;

    if not DirExists(XamppRoot) then
    begin
      MsgBox('المسار غير موجود: ' + XamppRoot, mbError, MB_OK);
      Result := False;
      exit;
    end;

    if not DirExists(AddBackslash(XamppRoot) + 'htdocs') then
    begin
      MsgBox('هذا المسار لا يبدو أنه XAMPP صحيح (مجلد htdocs غير موجود).', mbError, MB_OK);
      Result := False;
      exit;
    end;
  end;
end;

procedure InitializeWizard;
begin
  ConfigPage := CreateInputQueryPage(
    wpWelcome,
    'إعدادات التثبيت',
    'حدد مسار XAMPP ومنافذ Apache / MySQL',
    'يمكنك الإبقاء على القيم الافتراضية إذا كنت تستخدم XAMPP القياسي.'
  );

  ConfigPage.Add('مسار XAMPP', False);
  ConfigPage.Add('منفذ Apache', False);
  ConfigPage.Add('منفذ MySQL', False);
  ConfigPage.Add('مستخدم MySQL', False);
  ConfigPage.Add('كلمة مرور MySQL (اتركها فارغة إذا لا توجد)', True);

  ConfigPage.Values[0] := 'C:\xampp';
  ConfigPage.Values[1] := '80';
  ConfigPage.Values[2] := '3306';
  ConfigPage.Values[3] := 'root';
  ConfigPage.Values[4] := '';
end;
