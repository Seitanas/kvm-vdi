
#include "Pch.h"

#include "OVirtCredProv.h"
#include "OVirtCredentials.h"
#include "Helpers.h"

OVirtCredentials::OVirtCredentials () :
	_cRef(1),
	_wzUserName(NULL),
	_wzPassword(NULL),
	_wzDomain(NULL)
{
	DllAddRef();
}

OVirtCredentials::~OVirtCredentials ()
{
	ResetCredentials();
	DllRelease();
}

	// IUnknown

IFACEMETHODIMP_(ULONG) OVirtCredentials::AddRef()
{
	return ++_cRef;
}

IFACEMETHODIMP_(ULONG) OVirtCredentials::Release()
{
	LONG cRef = --_cRef;
	if (!cRef)
	{
		delete this;
	}
	return cRef;
}

IFACEMETHODIMP OVirtCredentials::QueryInterface(__in REFIID riid, __deref_out void** ppv)
{
	HRESULT hr;
	if (ppv)
	{
		if ((IID_IUnknown == riid) || (IID_ICredentialProviderCredential == riid))
		{
			*ppv = static_cast<IUnknown*>(this);
			reinterpret_cast<IUnknown*>(*ppv)->AddRef();
			hr = S_OK;
		}
		else
		{
			*ppv = NULL;
			hr = E_NOINTERFACE;
		}
	}
	else
	{
		hr = E_INVALIDARG;
	}
	return hr;
}

	// ICredentialProviderCredential

IFACEMETHODIMP OVirtCredentials::Advise(__in ICredentialProviderCredentialEvents *pcpce)
{
	UNREFERENCED_PARAMETER(pcpce);
	return S_OK;
}

IFACEMETHODIMP OVirtCredentials::UnAdvise()
{
	return S_OK;
}

IFACEMETHODIMP OVirtCredentials::SetSelected(__out BOOL *pbAutoLogon)
{
	UNREFERENCED_PARAMETER(pbAutoLogon);
	return S_FALSE;
}

IFACEMETHODIMP OVirtCredentials::SetDeselected()
{
	return S_OK;
}

IFACEMETHODIMP OVirtCredentials::GetFieldState(__in DWORD dwFieldID,
											  __out CREDENTIAL_PROVIDER_FIELD_STATE *pcpfs,
											  __out CREDENTIAL_PROVIDER_FIELD_INTERACTIVE_STATE *pcpfis)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(pcpfs);
	UNREFERENCED_PARAMETER(pcpfis);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::GetStringValue(__in DWORD dwFieldID, __deref_out PWSTR* ppwsz)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(ppwsz);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::GetBitmapValue(__in DWORD dwFieldID, __out HBITMAP* phbmp)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(phbmp);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::GetCheckboxValue(__in DWORD dwFieldID, 
												 __out BOOL* pbChecked, 
												 __deref_out PWSTR* ppwszLabel)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(pbChecked);
	UNREFERENCED_PARAMETER(ppwszLabel);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::GetComboBoxValueCount(__in DWORD dwFieldID,
													  __out DWORD* pcItems,
													  __out_range(<,*pcItems) DWORD* pdwSelectedItem)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(pcItems);
	UNREFERENCED_PARAMETER(pdwSelectedItem);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::GetComboBoxValueAt(__in DWORD dwFieldID,
												   __in DWORD dwItem,
												   __deref_out PWSTR* ppwszItem)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(dwItem);
	UNREFERENCED_PARAMETER(ppwszItem);
	return E_NOTIMPL;

}

IFACEMETHODIMP OVirtCredentials::GetSubmitButtonValue(__in DWORD dwFieldID,
													 __out DWORD* pdwAdjacentTo)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(pdwAdjacentTo);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::SetStringValue(__in DWORD dwFieldID, __in PCWSTR pwz)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(pwz);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::SetCheckboxValue(__in DWORD dwFieldID, __in BOOL bChecked)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(bChecked);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::SetComboBoxSelectedValue(__in DWORD dwFieldID,
														 __in DWORD dwSelectedItem)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	UNREFERENCED_PARAMETER(dwSelectedItem);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::CommandLinkClicked(__in DWORD dwFieldID)
{
	UNREFERENCED_PARAMETER(dwFieldID);
	return E_NOTIMPL;
}

IFACEMETHODIMP OVirtCredentials::GetSerialization(__out CREDENTIAL_PROVIDER_GET_SERIALIZATION_RESPONSE* pcpgsr, 
												 __out CREDENTIAL_PROVIDER_CREDENTIAL_SERIALIZATION* pcpcs, 
												 __deref_out_opt PWSTR* ppwszOptionalStatusText, 
												 __out CREDENTIAL_PROVIDER_STATUS_ICON* pcpsiOptionalStatusIcon)
{
	UNREFERENCED_PARAMETER(ppwszOptionalStatusText);
	UNREFERENCED_PARAMETER(pcpsiOptionalStatusIcon);

	KERB_INTERACTIVE_UNLOCK_LOGON kiul;
	ZeroMemory(&kiul, sizeof(kiul));

	KERB_INTERACTIVE_LOGON *pkil = &kiul.Logon;

	HRESULT hr;

	// Initialize the UNICODE_STRINGS to share our username and password strings
	hr = UnicodeStringInitWithString(_wzDomain, &pkil->LogonDomainName);
	if (SUCCEEDED(hr))
	{
		hr = UnicodeStringInitWithString(_wzUserName, &pkil->UserName);
		if (SUCCEEDED(hr))
		{
			hr = UnicodeStringInitWithString(_wzPassword, &pkil->Password);
			if (SUCCEEDED(hr))
			{
				//
				// Allocate copies of, and package, the strings in a binary blob
				//
				pkil->MessageType = ((_cpus == CPUS_UNLOCK_WORKSTATION) ? KerbWorkstationUnlockLogon : KerbInteractiveLogon);

				hr = KerbInteractiveUnlockLogonPack(kiul, &pcpcs->rgbSerialization, &pcpcs->cbSerialization);
				if (SUCCEEDED(hr))
				{
					ULONG ulAuthPackage;

					hr = RetrieveNegotiateAuthPackage(&ulAuthPackage);
					if (SUCCEEDED(hr))
					{
						pcpcs->ulAuthenticationPackage = ulAuthPackage;
						pcpcs->clsidCredentialProvider = CLSID_OVirtProvider;

						// At this point the credential has created the serialized credential used for logon
						// By setting this to CPGSR_RETURN_CREDENTIAL_FINISHED we are letting logonUI know
						// that we have all the information we need and it should attempt to submit the 
						// serialized credential.
						*pcpgsr = CPGSR_RETURN_CREDENTIAL_FINISHED;
					}
				}
			}
		}
	}

	return hr;
}

IFACEMETHODIMP OVirtCredentials::ReportResult(__in NTSTATUS ntsStatus,
											 __in NTSTATUS ntsSubstatus,
											 __deref_out_opt PWSTR* ppwszOptionalStatusText,
											 __out CREDENTIAL_PROVIDER_STATUS_ICON* pcpsiOptionalStatusIcon)
{
	UNREFERENCED_PARAMETER(ntsStatus);
	UNREFERENCED_PARAMETER(ntsStatus);
	UNREFERENCED_PARAMETER(ntsSubstatus);
	UNREFERENCED_PARAMETER(ppwszOptionalStatusText);
	UNREFERENCED_PARAMETER(pcpsiOptionalStatusIcon);
	return E_NOTIMPL;
}

bool OVirtCredentials::GotCredentials()
{
	return ((_wzUserName != NULL) && (_wzPassword != NULL) && (_wzDomain != NULL));
}

void OVirtCredentials::SetCredentials(LPCWSTR wzUserName, LPCWSTR wzPassword, LPCWSTR wzDomain)
{
	ASSERT(wzUserName != NULL);
	ASSERT(wzPassword != NULL);

	ResetCredentials();

	if (wzUserName != NULL)
	{
		_wzUserName = _wcsdup(wzUserName);
	}

	if (wzPassword != NULL)
	{
		_wzPassword = _wcsdup(wzPassword);
	}

	if (wzDomain != NULL)
	{
		_wzDomain = _wcsdup(wzDomain);
	}
	else
	{
		WCHAR wsz[MAX_COMPUTERNAME_LENGTH+1];
		DWORD cch = ARRAYSIZE(wsz);

		if (::GetComputerNameW(wsz, &cch))
		{
			_wzDomain = _wcsdup(wsz);
		}
	}

	if (GotCredentials() == false)
	{
		ResetCredentials();
	}
}

void OVirtCredentials::ResetCredentials()
{
	if (_wzUserName != NULL)
	{
		free(_wzUserName);
		_wzUserName = NULL;
	}

	if (_wzPassword != NULL)
	{
		::SecureZeroMemory(_wzPassword, wcslen(_wzPassword) * sizeof(WCHAR));
		free(_wzPassword);
		_wzPassword = NULL;
	}

	if (_wzDomain != NULL)
	{
		free(_wzDomain);
		_wzDomain = NULL;
	}
}

void OVirtCredentials::SetUsageScenario(CREDENTIAL_PROVIDER_USAGE_SCENARIO cpus)
{
	_cpus = cpus;
}
