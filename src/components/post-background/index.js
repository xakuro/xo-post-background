import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import {
	ButtonGroup,
	Button,
	ResponsiveWrapper,
	SelectControl,
	Flex,
	FlexBlock,
	ToggleControl,
	ColorPalette,
	GradientPicker,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

const PostBackgroundSidebar = () => {
	const postType = useSelect(
		(select) => select('core/editor').getCurrentPostType(),
		[]
	);

	if (!postType) {
		return null;
	}

	const [meta, setMeta] = useEntityProp('postType', postType, 'meta');

	// console.log('meta', meta);

	if (meta._xo_background.id == null && meta._background_id != 0) {
		meta._xo_background.id = meta._background_id;
	}

	if (meta._xo_background.id != null) {
		meta._background_id = null;
	}

	meta._xo_background.repeat = meta._xo_background.repeat ?? true;
	meta._xo_background.attachment = meta._xo_background.attachment ?? true;
	meta._xo_background.enable_color = meta._xo_background.enable_color ?? false;

	const media = useSelect((select) =>
		select('core').getMedia(meta._xo_background.id)
	);

	const onRemoveImage = () => {
		setMeta({ _background_id: null });
		setMeta({ _xo_background: { ...meta._xo_background, id: 0 } });
	};

	const onUpdateImage = (value) => {
		setMeta({ _xo_background: { ...meta._xo_background, id: value.id } });
	};

	const onUpdatePositionX = (value) => {
		setMeta({
			_xo_background: { ...meta._xo_background, position_x: value },
		});
	};

	const onUpdatePositionY = (value) => {
		setMeta({
			_xo_background: { ...meta._xo_background, position_y: value },
		});
	};

	const onUpdateSize = (value) => {
		setMeta({ _xo_background: { ...meta._xo_background, size: value } });
	};

	const onUpdateRepeat = (value) => {
		setMeta({
			_xo_background: { ...meta._xo_background, repeat: value },
		});
	};

	const onUpdateAttachment = (value) => {
		setMeta({
			_xo_background: { ...meta._xo_background, attachment: value },
		});
	};

	const onUpdateEnableColor = (value) => {
		setMeta({
			_xo_background: { ...meta._xo_background, enable_color: value },
		});
	};

	const onUpdateColor = (value) => {
		setMeta({ _xo_background: { ...meta._xo_background, color: value } });
	};

	const onUpdateGradient = (value) => {
		setMeta({ _xo_background: { ...meta._xo_background, custom_gradient: value } });
	};

	const onUpdateColorSolidButton = () => {
		setMeta({ _xo_background: { ...meta._xo_background, gradient: '' } });
	};

	const onUpdateColorGradientButton = () => {
		setMeta({ _xo_background: { ...meta._xo_background, gradient: 'custom' } });
	};

	return (
		<PluginDocumentSettingPanel
			title={__('Background', 'xo-post-background')}
		>
			<div className="editor-post-featured-image">
				<MediaUploadCheck>
					<MediaUpload
						onSelect={onUpdateImage}
						value={meta._xo_background.id}
						allowedTypes={['image']}
						render={({ open }) => (
							<div className="editor-post-featured-image__container">
								<Button
									className={!meta._xo_background.id ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview'}
									onClick={open}
								>
									{!meta._xo_background.id && __('Set background image', 'xo-post-background')}
									{media != undefined && (
										<ResponsiveWrapper
											naturalWidth={
												media.media_details.width
											}
											naturalHeight={
												media.media_details.height
											}
										>
											<img src={media.source_url} />
										</ResponsiveWrapper>
									)}
								</Button>

								{ meta._xo_background.id > 0 && (
									<HStack className="editor-post-featured-image__actions">
										<Button
											className="editor-post-featured-image__action"
											onClick={ open }
											aria-hidden="true"
										>
											{ __( 'Replace', 'xo-post-background' ) }
										</Button>
										<Button
											className="editor-post-featured-image__action"
											onClick={ () => {
												onRemoveImage();
												toggleRef.current.focus();
											} }
											// To support WordPress version 6.2 and earlier.
											style={{ marginTop: 0 }}
										>
											{ __( 'Remove', 'xo-post-background' ) }
										</Button>
									</HStack>
								) }
							</div>
						)}
					/>
				</MediaUploadCheck>
			</div>

			{meta._xo_background.id > 0 && (
				<div
					className="editor-post-background-options"
					style={{ marginTop: 20 }}
				>
					<ToggleControl
						label={__('Repeat Background Image', 'xo-post-background')}
						checked={meta._xo_background.repeat}
						onChange={onUpdateRepeat}
					/>

					<ToggleControl
						label={__('Scroll with Page', 'xo-post-background')}
						checked={meta._xo_background.attachment}
						onChange={onUpdateAttachment}
					/>

					<Flex direction="row" align="baseline">
						<FlexBlock>
							<SelectControl
								label={__(
									'Horizontal position',
									'xo-post-background'
								)}
								value={meta._xo_background.position_x}
								options={[
									{
										label: __('Left edge', 'xo-post-background'),
										value: 'left',
									},
									{
										label: __('Center', 'xo-post-background'),
										value: 'center',
									},
									{
										label: __('Right edge', 'xo-post-background'),
										value: 'right',
									},
								]}
								onChange={onUpdatePositionX}
							/>
						</FlexBlock>

						<FlexBlock>
							<SelectControl
								label={__('Vertical position', 'xo-post-background')}
								value={meta._xo_background.position_y}
								options={[
									{
										label: __('Top edge', 'xo-post-background'),
										value: 'top',
									},
									{
										label: __('Center', 'xo-post-background'),
										value: 'center',
									},
									{
										label: __('Bottom edge', 'xo-post-background'),
										value: 'bottom',
									},
								]}
								onChange={onUpdatePositionY}
							/>
						</FlexBlock>
					</Flex>

					<SelectControl
						label={__('Image Size', 'xo-post-background')}
						value={meta._xo_background.size}
						options={[
							{
								label: __('Original Size', 'xo-post-background'),
								value: 'auto',
							},
							{
								label: __('Fit to Screen', 'xo-post-background'),
								value: 'contain',
							},
							{
								label: __('Fill Screen', 'xo-post-background'),
								value: 'cover',
							},
						]}
						onChange={onUpdateSize}
					/>
				</div>
			)}

			<div
				className="editor-post-background-color"
				style={{ marginTop: 20 }}
			>
				<ToggleControl
					label={__('Background Color', 'xo-post-background')}
					checked={meta._xo_background.enable_color}
					onChange={onUpdateEnableColor}
				/>

				{meta._xo_background.enable_color && (
					<>
						{typeof GradientPicker === 'undefined' ? (
							<ColorPalette
								value={meta._xo_background.color}
								onChange={onUpdateColor}
							/>
						) : (
							<>
								<ButtonGroup style={{ marginBottom: 12 }}>
									<Button
										isSmall
										variant={! meta._xo_background.gradient ? 'primary' : undefined}
										onClick={onUpdateColorSolidButton}
									>
										{__('Solid', 'xo-post-background')}
									</Button>
									<Button
										isSmall
										variant={meta._xo_background.gradient ? 'primary' : undefined}
										onClick={onUpdateColorGradientButton}
									>
										{__('Gradient', 'xo-post-background')}
									</Button>
								</ButtonGroup>

								{! meta._xo_background.gradient ? (
									<ColorPalette
										enableAlpha
										value={meta._xo_background.color}
										onChange={onUpdateColor}
									/>
								) : (
									<GradientPicker
										gradients={[]}
										value={'' === meta._xo_background.custom_gradient ? null : meta._xo_background.custom_gradient}
										onChange={onUpdateGradient}
									/>
								)}
							</>
						)}
					</>
				)}
			</div>
		</PluginDocumentSettingPanel>
	);
};

export default PostBackgroundSidebar;
